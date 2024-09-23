document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarUser();
    }
});

function dataCariDaftarUser() {
    const kataKunciData = $("#kataKunciData").val();

    if (kataKunciData) {
        $.ajax({
            url: "datadaftaruser.php",
            type: "post",
            data: {
                kataKunciData: kataKunciData,
                flag: "cari",
            },
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftarUser").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftarUser() {
    $.ajax({
        url: "datadaftaruser.php",
        type: "post",
        data: {
            flag: "daftar",
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            $("#dataDaftarUser").html(data);
            $(".overlay").hide();
        },
    });
}

function getFormUser(idUserAccount = "") {
    $("#modalFormUser").modal("show");
    $.ajax({
        url: "formuser.php",
        type: "post",
        data: {
            idUserAccount,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#dataFormUser").html(data);
            $("select.selectpicker").selectpicker();

            $(".check-menu").bootstrapSwitch();
        },
    });
}

function prosesUser() {
    const formUser = document.getElementById("formUser");
    const dataForm = new FormData(formUser);

    let validasi;

    if (dataForm.get("flag") === "tambah") {
        validasi = formValidation(dataForm);
    } else if (dataForm.get("flag") === "update") {
        validasi = formValidation(dataForm, ["password"]);
    }

    if (validasi) {
        $.ajax({
            url: "prosesuser.php",
            type: "post",
            enctype: "multipart/form-data",
            processData: false,
            contentType: false,
            data: dataForm,
            dataType: "json",

            beforeSend: function () {},

            success: function (data) {
                const { status, pesan } = data;

                if (status) {
                    dataDaftarUser();

                    if (dataForm.get("flag") === "tambah") {
                        getFormUser();
                    } else if (dataForm.get("flag") === "update") {
                        $("#modalFormUser").modal("hide");
                    }
                }

                notifikasi(status, pesan);
            },
        });
    }
}

function konfirmasiBatalUser(id, token) {
    Swal.fire({
        title: "Apakah Anda Yakin ?",
        text: "Setelah dibatalkan, proses tidak dapat diulangi!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya!",
        cancelButtonText: "Tidak!",
    }).then(function (result) {
        if (result.value) {
            $.ajax({
                url: "prosesuser.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idUserAccount: id,
                    flag: "delete",
                },
                dataType: "json",
                success: function (data) {
                    //console.log(data);
                    const { status, pesan } = data;

                    dataDaftarUser();
                    notifikasi(status, pesan);
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}

function notifikasi(status, pesan) {
    if (status === true) {
        toastr.success(pesan);
    } else {
        toastr.error(pesan);
    }
}
