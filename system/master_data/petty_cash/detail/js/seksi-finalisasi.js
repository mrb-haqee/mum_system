function seksiFormFinalisasi() {
    const kodePettyCash = $("#kodePettyCash").val();
    $.ajax({
        url: "seksi_finalisasi/form-finalisasi.php",
        type: "post",
        data: {
            kodePettyCash: kodePettyCash,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailPettyCash").html(data);
            $(".loader-custom").hide();
        },
    });
}

function prosesFinalisasi() {
    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();
    const kodePettyCash = $("input[name=kodePettyCash]").val();

    $.ajax({
        url: "seksi_finalisasi/proses-finalisasi.php",
        type: "post",
        data: {
            flag: "finalisasi",
            tokenCSRFForm,
            kodePettyCash,
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
