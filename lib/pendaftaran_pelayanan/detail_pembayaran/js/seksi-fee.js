function seksiFormFee() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();

    const param = $("#param").val();

    $.ajax({
        url: "seksi_fee/form-fee.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeRM: kodeRM,
            param: param,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailPembayaran").html(data);
            $(".loader-custom").hide();
            $("select.selectpicker").selectpicker();

            selectReferrer(kodeAntrian);
        },
    });
}

function selectReferrer(kodeAntrian = "") {
    const referrer = $("#referrer").val();
    const param = $("#param").val();

    $.ajax({
        url: "seksi_fee/select-fee.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            referrer,
            param,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#boxSelectReferrer").html(data);
            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesFee() {
    const formFee = document.getElementById("formFee");
    const dataForm = new FormData(formFee);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_fee/proses-fee.php",
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

                seksiFormFee();
            },
        });
    }
}
