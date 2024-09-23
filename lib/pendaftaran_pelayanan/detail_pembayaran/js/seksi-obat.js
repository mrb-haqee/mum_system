function seksiFormObat() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();

    const param = $("#param").val();

    $.ajax({
        url: "seksi_obat/form-obat.php",
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
            $("select.select2").select2();

            getDetailDosis();
        },
    });
}

function getDetailDosis() {
    const kodeAntrian = $("#kodeAntrian").val();
    const idObat = $("#idObat").val();

    const param = $("#param").val();

    $.ajax({
        url: "seksi_obat/detail-dosis.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            idObat,
            param,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#boxDetailDosis").html(data);

            $("select.selectpicker").selectpicker();
            $("select.select2").select2();

            getDosageResult();
        },
    });
}

function prosesObat() {
    const formObat = document.getElementById("formObat");
    const dataForm = new FormData(formObat);

    const validasi = formValidation(dataForm, [
        "dosageForm",
        "dosageRate",
        "dosageQty",
        "dosageUnit",
        "dosageDuration",
        "result",
        "idDiagnosisAcuan",
    ]);

    if (validasi) {
        $.ajax({
            url: "seksi_obat/proses-obat.php",
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

                seksiFormObat();
            },
        });
    }
}

function getDosageResult() {
    const $qty = $("#dosageQty");
    const $rate = $("#dosageRate");

    const $form = $("#dosageForm");
    const unit = $("#dosageUnit").data("code");
    const $duration = $("#dosageDuration");

    const $result = $("#result");

    $result.val(
        `(${$form.val() || "-"}) ${rupiahToNumber($rate.val()) || "-"} × ${rupiahToNumber($qty.val()) || "-"}${
            " " + unit || "-"
        }, selama ${rupiahToNumber($duration.val())} hari`
    );
}
