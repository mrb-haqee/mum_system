document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarPemasukanPengeluaranLain();
    }
});

$(document).ready(function () {
    $("#periode").daterangepicker({
        buttonClasses: " btn",
        applyClass: "btn-primary",
        cancelClass: "btn-secondary",
        locale: {
            format: "YYYY-MM-DD",
        },
    });
});

function dataDaftarPemasukanPengeluaranLain() {
    const periode = $("#periode").val();

    $.ajax({
        url: "daftar-pemasukan-pengeluaran-lain.php",
        type: "post",
        data: {
            periode,
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            $("#boxDaftarPemasukanPengeluaranLain").html(data);
            $(".overlay").hide();
        },
    });
}

function getFormPemasukanPengeluaranLain(idPemasukanPengeluaranLain = "") {
    $("#modalFormPemasukanPengeluaranLain").modal("show");
    $.ajax({
        url: "form-pemasukan-pengeluaran-lain.php",
        type: "post",
        data: {
            idPemasukanPengeluaranLain,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#boxFormPemasukanPengeluaranLain").html(data);
            $("select.selectpicker").selectpicker();

            selectRekening(idPemasukanPengeluaranLain);
        },
    });
}

function selectRekening(idPemasukanPengeluaranLain = "") {
    const jenisRekening = $("#jenisRekening").val();
    $.ajax({
        url: "select-rekening.php",
        type: "post",
        data: {
            idPemasukanPengeluaranLain,
            jenisRekening,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#boxRekening").html(data);
            $("select.selectpicker").selectpicker();

            selectCurrency(idPemasukanPengeluaranLain);
        },
    });
}

function selectCurrency(idPemasukanPengeluaranLain = "") {
    const jenisRekening = $("#jenisRekening").val();
    const idBank = $("#idBank").val();

    $.ajax({
        url: "select-currency.php",
        type: "post",
        data: {
            idPemasukanPengeluaranLain,
            jenisRekening,
            idBank,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#boxPembayaran").html(data);
            $("select.select2").select2();
        },
    });
}

function prosesPemasukanPengeluaranLain() {
    const formPemasukanPengeluaranLain = document.getElementById("formPemasukanPengeluaranLain");
    const dataForm = new FormData(formPemasukanPengeluaranLain);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "proses-pemasukan-pengeluaran-lain.php",
            type: "post",
            enctype: "multipart/form-data",
            processData: false,
            contentType: false,
            data: dataForm,
            dataType: "json",

            beforeSend: function () {},

            success: function (data) {
                const { status, pesan } = data;

                if (dataForm.get("flag") === "update") {
                    $("#modalFormPemasukanPengeluaranLain").modal("hide");
                } else if (dataForm.get("flag") === "tambah") {
                    getFormPemasukanPengeluaranLain();
                }

                if (status) {
                    dataDaftarPemasukanPengeluaranLain();
                }

                notifikasi(status, pesan);
            },
        });
    }
}

function deletePemasukanPengeluaranLain(id, token) {
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
                url: "proses-pemasukan-pengeluaran-lain.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idPemasukanPengeluaranLain: id,
                    flag: "delete",
                },
                dataType: "json",
                success: function (data) {
                    //console.log(data);
                    const { status, pesan } = data;

                    dataDaftarPemasukanPengeluaranLain();
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
