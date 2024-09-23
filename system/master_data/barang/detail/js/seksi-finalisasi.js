function seksiFormFinalisasi() {
    let kodeBarang = $("#kodeBarang").val();
    $.ajax({
        url: "seksi_finalisasi/form-finalisasi.php",
        type: "post",
        data: {
            kodeBarang: kodeBarang,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailInventoryBarang").html(data);
            $(".loader-custom").hide();
        },
    });
}

function prosesFinalisasi() {
    const kodeBarang = $("input[name=kodeBarang]").val();
    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();

    $.ajax({
        url: "seksi_finalisasi/proses-finalisasi.php",
        type: "post",
        data: {
            flag: "finalisasi",
            kodeBarang,
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
