function seksiFormFinalisasi() {
    const kodeBank = $("#kodeBank").val();
    $.ajax({
        url: "seksi_finalisasi/form-finalisasi.php",
        type: "post",
        data: {
            kodeBank: kodeBank,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailBank").html(data);
            $(".loader-custom").hide();
        },
    });
}

function prosesFinalisasi() {
    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();
    const kodeBank = $("input[name=kodeBank]").val();

    $.ajax({
        url: "seksi_finalisasi/proses-finalisasi.php",
        type: "post",
        data: {
            flag: "finalisasi",
            tokenCSRFForm,
            kodeBank,
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
