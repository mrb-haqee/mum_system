function seksiFormPaketLaboratorium() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();

    const param = $("#param").val();

    $.ajax({
        url: "seksi_paket_lab/form-paket-laboratorium.php",
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

function prosesPaketLaboratorium() {
    const formPaketLaboratorium = document.getElementById("formPaketLaboratorium");
    const dataForm = new FormData(formPaketLaboratorium);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_paket_lab/proses-paket-laboratorium.php",
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

                seksiFormPaketLaboratorium();
            },
        });
    }
}
