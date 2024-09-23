function seksiFormFinalisasi() {
    const kodeVendor = $("#kodeVendor").val();
    $.ajax({
        url: "seksi_finalisasi/form-finalisasi.php",
        type: "post",
        data: {
            kodeVendor: kodeVendor,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailVendor").html(data);
            $(".loader-custom").hide();
        },
    });
}

function prosesFinalisasi() {
    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();
    const kodeVendor = $("input[name=kodeVendor]").val();

    $.ajax({
        url: "seksi_finalisasi/proses-finalisasi.php",
        type: "post",
        data: {
            flag: "finalisasi",
            tokenCSRFForm,
            kodeVendor,
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
