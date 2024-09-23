function seksiFormTindakan() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();

    const param = $("#param").val();

    $.ajax({
        url: "seksi_tindakan/form-tindakan.php",
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

function prosesTindakan() {
    const formTindakan = document.getElementById("formTindakan");
    const dataForm = new FormData(formTindakan);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_tindakan/proses-tindakan.php",
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

                seksiFormTindakan();
            },
        });
    }
}
