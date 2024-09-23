function seksiFormAdmisi() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();

    const paramAdmisi = $("input[name=paramAdmisi]").val();

    $.ajax({
        url: "seksi_admisi/form-admisi.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeRM: kodeRM,
            paramAdmisi,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailPendaftaran").html(data);
            $(".loader-custom").hide();

            $("select.selectpicker").selectpicker();
            $("select.select2").select2();

            const dt_element = $("#waktuKedatangan");
            dt_element.datetimepicker({
                defaultDate: dt_element.data("value"),
            });
        },
    });
}

function prosesAdmisi() {
    const formAdmisi = document.getElementById("formAdmisi");
    const dataForm = new FormData(formAdmisi);

    const validasi = formValidation(dataForm, ["kategoriAlergi", "kodeAlergi", "waktuKedatangan"]);

    if (validasi) {
        $.ajax({
            url: "seksi_admisi/proses-admisi.php",
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
                seksiFormAdmisi();
            },
        });
    }
}
