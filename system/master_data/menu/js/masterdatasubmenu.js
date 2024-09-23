function getFormSubMenu(idMenu, idSubMenu = "") {
    $("#modalFormSubMenu").modal("show");

    $.ajax({
        url: "sub_menu/dataformsubmenu.php",
        type: "post",
        data: {
            idMenu,
            idSubMenu,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#dataFormSubMenu").empty().html(data);
            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesSubMenu() {
    let formSubMenu = document.getElementById("formSubMenu");
    let dataForm = new FormData(formSubMenu);

    const validasi = formValidation(dataForm);
    
    if (validasi) {
        $.ajax({
            url: "sub_menu/prosessubmenu.php",
            type: "post",
            enctype: "multipart/form-data",
            processData: false,
            contentType: false,
            data: dataForm,
            dataType: "json",

            beforeSend: function () {},

            success: function (data) {
                // console.log(data);
                const { status, pesan } = data;
                notifikasi(status, pesan);

                getFormSubMenu(dataForm.get("idMenu"));
            },
        });
    }
}

function konfirmasiBatalSubMenu(idMenu, id, token) {
    Swal.fire({
        title: "Apakah anda yakin?",
        text: "Setelah dibatalkan, proses tidak dapat diulangi!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya!",
        cancelButtonText: "Tidak!",
    }).then(function (result) {
        if (result.value) {
            $.ajax({
                url: "sub_menu/prosessubmenu.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idSubMenu: id,
                    flag: "delete",
                },
                dataType: "json",
                success: function (data) {
                    //console.log(data);
                    const { status, pesan } = data;

                    notifikasi(status, pesan);
                    getFormSubMenu(idMenu);
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}
