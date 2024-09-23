function seksiFormPembayaran() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();

    const param = $("#param").val();

    $.ajax({
        url: "seksi_pembayaran/form-pembayaran.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeRM: kodeRM,
            param,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailPembayaran").html(data);
            $(".loader-custom").hide();
            $("select.selectpicker").selectpicker();

            selectDetailPembayaran(kodeAntrian);

            const dt_element = $("#waktuKepulangan");
            dt_element.datetimepicker({
                defaultDate: dt_element.data("value"),
            });
        },
    });
}

function selectDetailPembayaran(kodeAntrian = "") {
    const kodeInvoice = $("input[name=kodeInvoice]").val();
    const param = $("#param").val();

    const metodePembayaran = $("#metodePembayaran").val();
    const sisaBayar = $("input[name=sisaBayar]").val();

    $.ajax({
        url: "seksi_pembayaran/detail-pembayaran.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeInvoice: kodeInvoice,
            metodePembayaran,
            sisaBayar,
            param,
        },
        success: function (data) {
            $("#boxDetailFinalisasiPembayaran").html(data);
            $("select.selectpicker").selectpicker();

            $("select.select2").select2();

            if (["Tunai", "Insurance"].includes(metodePembayaran)) {
                selectCurrency(kodeAntrian);
            } else if (metodePembayaran === "Non Tunai") {
                selectMetode(kodeAntrian);
            }
        },
    });
}

function selectMetode(kodeAntrian = "") {
    const kodeInvoice = $("input[name=kodeInvoice]").val();
    const param = $("#param").val();

    const kodeTujuanTransfer = $("#kodeTujuanTransfer").val();

    $.ajax({
        url: "seksi_pembayaran/select-metode.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeInvoice: kodeInvoice,
            kodeTujuanTransfer,
            param,
        },
        success: function (data) {
            $("#boxMetode").remove();
            $(data).insertAfter("#boxTujuanTransfer");

            $("select.selectpicker").selectpicker();
            selectEDC(kodeAntrian);
        },
    });
}

function selectEDC(kodeAntrian = "") {
    const kodeInvoice = $("input[name=kodeInvoice]").val();
    const param = $("#param").val();

    const metode = $("#metode").val();
    const kodeTujuanTransfer = $("#kodeTujuanTransfer").val();

    $.ajax({
        url: "seksi_pembayaran/select-edc.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeInvoice: kodeInvoice,
            kodeTujuanTransfer,
            metode,
            param,
        },
        success: function (data) {
            $("#boxEDC,#boxNoBatch").remove();
            $(data).insertAfter("#boxMetode");

            $("select.selectpicker").selectpicker();
        },
    });
}

function selectCurrency(kodeAntrian = "") {
    const kodeInvoice = $("input[name=kodeInvoice]").val();
    const param = $("#param").val();

    const currency = $("#currency").val();
    const metodePembayaran = $("#metodePembayaran").val();
    const sisaBayar = $("input[name=sisaBayar]").val();

    $.ajax({
        url: "seksi_pembayaran/select-currency.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeInvoice: kodeInvoice,
            metodePembayaran,
            currency,
            sisaBayar,
            param,
        },
        success: function (data) {
            $("#boxJumlahBayar,#boxJumlahBayarExc,#boxSisaBayarExc,#boxKembalianExc").remove();
            $(data).insertAfter("#boxCurrency");

            $("select.selectpicker").selectpicker();
            getKembalian();
        },
    });
}

function changeHarga(jenisItem, tokenCSRFForm, idItemKlinik, idHarga) {
    const kodeAntrian = $("#kodeAntrian").val();
    $.ajax({
        url: "seksi_pembayaran/proses-pembayaran.php",
        type: "post",
        data: {
            kodeAntrian,
            tokenCSRFForm,
            jenisItem,
            idItemKlinik,
            idHarga,
            flag: "change",
        },
        dataType: "json",
        success: function (data) {
            const { status, pesan } = data;
            notifikasi(status, pesan);

            seksiFormPembayaran();
        },
    });
}

function deletePembayaran(idPasienDeposit, tokenCSRFForm) {
    const kodeAntrian = $("#kodeAntrian").val();
    $.ajax({
        url: "seksi_pembayaran/proses-pembayaran.php",
        type: "post",
        data: {
            kodeAntrian,
            tokenCSRFForm,
            idPasienDeposit,
            flag: "deletePembayaran",
        },
        dataType: "json",
        success: function (data) {
            const { status, pesan } = data;
            notifikasi(status, pesan);

            seksiFormPembayaran();
        },
    });
}

function prosesPembayaran() {
    const formFinalisasiPembayaran = document.getElementById("formFinalisasiPembayaran");
    const dataForm = new FormData(formFinalisasiPembayaran);

    const validasi = formValidation(dataForm);
    if (validasi) {
        $.ajax({
            url: "seksi_pembayaran/proses-pembayaran.php",
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

                seksiFormPembayaran();
            },
        });
    }
}

function setWaktuKepulangan() {
    const kodeAntrian = $("#kodeAntrian").val();
    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();

    const waktuKepulangan = $("#waktuKepulangan").val();

    $.ajax({
        url: "seksi_pembayaran/proses-pembayaran.php",
        type: "post",
        data: {
            kodeAntrian,
            waktuKepulangan,
            tokenCSRFForm,
            flag: "setWaktuKepulangan",
        },
        dataType: "json",
        success: function (data) {
            const { status, pesan } = data;
            notifikasi(status, pesan);

            seksiFormPembayaran();
        },
    });
}

function bukaFinalisasiInvoice(kodeInvoice, kodeAntrian, tokenCSRFForm) {
    Swal.fire({
        title: "Apakah Anda Yakin ?",
        text: "Semua Pembayaran Invoice Ini Akan Terhapus!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya!",
        cancelButtonText: "Tidak!",
    }).then(function (result) {
        if (result.value) {
            $.ajax({
                url: "seksi_pembayaran/proses-pembayaran.php",
                type: "post",
                data: {
                    kodeInvoice,
                    kodeAntrian,
                    tokenCSRFForm,
                    flag: "bukaFinalisasi",
                },
                dataType: "json",
                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    seksiFormPembayaran();
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses Dibatalkan !", "error");
        }
    });
}

function getPayable() {
    const diskon = rupiahToNumber($("#diskon").val());
    const grandTotal = parseInt($("input[name=grandTotal]").val());

    const payable = grandTotal - diskon;

    if (payable < 0) {
        $("#diskon").val(grandTotal).trigger("keyup");
    } else {
        $("#payableText").text("Rp " + numberToRupiah(payable));
        $("input[name=payable]").val(payable);

        $("#jumlahBayar").val(numberToRupiah(payable)).trigger("keyup");
    }
}

function getKembalian() {
    const sisaBayar = parseInt($("input[name=sisaBayar]").val());
    const sisaBayarExc = rupiahToNumber($("#sisaBayarExc").val());

    const jumlahBayar = rupiahToNumber($("#jumlahBayar").val());
    const jumlahBayarExc = rupiahToNumber($("#jumlahBayarExc").val());

    const currency = $("#currency").val();

    const selisih = Number(sisaBayar - jumlahBayar).toFixed(0);
    const selisihExc = Number(sisaBayarExc - jumlahBayarExc).toFixed(2);

    const $selisihPembayaran = $("#selisihPembayaran");
    const $selisihLabel = $("#selisihLabel");

    const $selisihPembayaranExc = $("#selisihPembayaranExc");

    if (selisih > 0) {
        $selisihPembayaran.val(numberToRupiah(selisih));
        $selisihPembayaranExc.val(numberToRupiah(selisihExc));

        $selisihLabel.text("KURANG BAYAR");
    } else {
        $selisihPembayaran.val(numberToRupiah(Math.abs(selisih)));
        $selisihPembayaranExc.val(numberToRupiah(Math.abs(selisihExc)));

        $selisihLabel.text("KEMBALIAN");
    }
}

async function getExchangeValue() {
    const $jumlahBayar = $("#jumlahBayar");
    const currency = $("#currency").val();
    const jumlahBayarExc = $("#jumlahBayarExc").val();

    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();

    const { result: excValue } = await $.ajax({
        url: "seksi_pembayaran/exchange.php",
        type: "post",
        data: {
            tokenCSRFForm,
            currency,
            jumlahBayarExc,
        },
        dataType: "json",
    });

    $jumlahBayar.val(numberToRupiah(excValue)).trigger("keyup");
}
