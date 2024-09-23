function getFormAksesMenu(idUserAccount, idUserDetail = "") {
    $("#modalFormAksesMenu").modal("show");

    $.ajax({
        url: "akses_menu/dataformaksesmenu.php",
        type: "post",
        data: {
            idUserAccount,
            idUserDetail,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#dataFormAksesMenu").empty().html(data);
            $("select.selectpicker").selectpicker();

            dataDaftarSubMenuUser(idUserAccount);
            dataDaftarSubMenuPerMenu(idUserAccount, idUserDetail);
        },
    });
}

function dataDaftarSubMenuUser(idUserAccount) {
    $.ajax({
        url: "akses_menu/datadaftarsubmenuuser.php",
        type: "post",
        data: {
            idUserAccount,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#dataDaftarSubMenuUser").html(data);
        },
    });
}

function dataDaftarSubMenuPerMenu(idUserAccount) {
    const idMenu = $("#idMenu").val();
    $.ajax({
        url: "akses_menu/datadaftarsubmenupermenu.php",
        type: "post",
        data: {
            idUserAccount,
            idMenu,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#dataDaftarSubMenu").html(data);
            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesAksesMenu() {
    const formAksesMenu = document.getElementById("formAksesMenu");
    const dataForm = new FormData(formAksesMenu);

    const hasilValidasi = formValidation(dataForm);
    if (hasilValidasi) {
        $.ajax({
            url: "akses_menu/prosesaksesmenu.php",
            type: "post",
            enctype: "multipart/form-data",
            processData: false,
            contentType: false,
            data: dataForm,
            dataType: "json",

            beforeSend: function () {},

            success: function (data) {
                const { status, pesan } = data;

                notifikasi(status, pesan);

                dataDaftarSubMenuUser(dataForm.get("idUserAccount"));
                getFormAksesMenu(dataForm.get("idUserAccount"));
            },
        });
    }
}

function konfirmasiBatalAksesMenu(idUserAccount, id, token) {
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
                url: "akses_menu/prosesaksesmenu.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idUserDetail: id,
                    flag: "delete",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    dataDaftarSubMenuUser(idUserAccount);
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}

