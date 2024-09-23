function seksiFormLaboratorium() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();

    const param = $("#param").val();

    $.ajax({
        url: "seksi_laboratorium/form-laboratorium.php",
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

            selectRujukan();
        },
    });
}

function selectRujukan() {
    const kodeAntrian = $("#kodeAntrian").val();
    const idProsedurLaboratorium = $("#idProsedurLaboratorium").val();

    $.ajax({
        url: "seksi_laboratorium/select-laboratorium.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            idProsedurLaboratorium,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#boxSelectRujukan").html(data);
            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesLaboratorium() {
    const formLaboratorium = document.getElementById("formLaboratorium");
    const dataForm = new FormData(formLaboratorium);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_laboratorium/proses-laboratorium.php",
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

                seksiFormLaboratorium();
            },
        });
    }
}
