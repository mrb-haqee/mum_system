function seksiFormEscort() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();

    const param = $("#param").val();

    $.ajax({
        url: "seksi_escort/form-escort.php",
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
        },
    });
}

function prosesEscort() {
    const formEscort = document.getElementById("formEscort");
    const dataForm = new FormData(formEscort);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_escort/proses-escort.php",
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

                seksiFormEscort();
            },
        });
    }
}
