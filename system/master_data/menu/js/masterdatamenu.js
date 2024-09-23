document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarMenu();

        $.fn.selectpicker.Constructor.DEFAULTS.multipleSeparator = " ";

        $(document).on("change", "#classFA", function (e) {
            const value = $(e.target).val();

            if (/fa(s|r|b)\sfa\-[\w-]+/g.test(value)) {
                $("#iconSample").attr("class", value);
            } else {
                $("#iconSample").attr("class", "fas fa-spinner fa-spin");
            }
        });
    }
});

function dataCariDaftarMenu() {
    kataKunciData = $("#kataKunciData").val();

    if (kataKunciData) {
        $.ajax({
            url: "datadaftarmenu.php",
            type: "post",
            data: {
                kataKunciData: kataKunciData,
                flagData: "cari",
            },
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftarMenu").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftarMenu() {
    $.ajax({
        url: "datadaftarmenu.php",
        type: "post",
        data: {
            flagData: "daftar",
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            $("#dataDaftarMenu").html(data);
            $(".overlay").hide();
        },
    });
}

function getFormMenu(idMenu = "") {
    $("#modalFormMenu").modal("show");

    $.ajax({
        url: "dataformmenu.php",
        type: "POST",
        data: {
            idMenu,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#dataFormMenu").empty().html(data);
            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesMenu() {
    let formMenu = document.getElementById("formMenu");
    let dataForm = new FormData(formMenu);

    const validasi = formValidation(dataForm);
    if (validasi) {
        $.ajax({
            url: "prosesmenu.php",
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
                dataDaftarMenu();
            },
        });
    }
}

function konfirmasiBataMenu(id, token) {
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
                url: "prosesmenu.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idMenu: id,
                    flag: "delete",
                },
                dataType: "json",
                success: function (data) {
                    const { status, pesan } = data;

                    notifikasi(status, pesan);
                    dataDaftarMenu();
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
