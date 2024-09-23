function seksiFormFinalisasi() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();

    $.ajax({
        url: "seksi_finalisasi/form-finalisasi.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeRM: kodeRM,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            //console.log(data);
            $("#formDetailPembayaran").html(data);
            $(".loader-custom").hide();
        },
    });
}

function prosesFinalisasi() {
    const kodeAntrian = $("input[name=kodeAntrian]").val();
    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();

    $.ajax({
        url: "seksi_finalisasi/proses-finalisasi.php",
        type: "post",
        data: {
            flag: "finalisasi",
            kodeAntrian,
            tokenCSRFForm,
        },
        dataType: "json",

        beforeSend: function () {},

        success: function (data) {
            const { status, pesan } = data;

            notifikasi(status, pesan);

            if (status) {
                setTimeout(() => {
                    window.location.href = "../";
                }, 500);
            }
        },
    });
}
