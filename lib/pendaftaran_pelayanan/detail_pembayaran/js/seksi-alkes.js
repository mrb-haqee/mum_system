function seksiFormAlkes() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();

    const param = $("#param").val();

    $.ajax({
        url: "seksi_alkes/form-alkes.php",
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
        },
    });
}

function prosesAlkes() {
    const formAlkes = document.getElementById("formAlkes");
    const dataForm = new FormData(formAlkes);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_alkes/proses-alkes.php",
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

                seksiFormAlkes();
            },
        });
    }
}
