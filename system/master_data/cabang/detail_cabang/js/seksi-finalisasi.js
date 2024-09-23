function seksiFormFinalisasi() {
    const kodeCabang = $("#kodeCabang").val();
    $.ajax({
        url: "seksi_finalisasi/form-finalisasi.php",
        type: "post",
        data: {
            kodeCabang: kodeCabang,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailCabang").html(data);
            $(".loader-custom").hide();
        },
    });
}

function prosesFinalisasi() {
    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();
    const kodeCabang = $("input[name=kodeCabang]").val();

    $.ajax({
        url: "seksi_finalisasi/proses-finalisasi.php",
        type: "post",
        data: {
            flag: "finalisasi",
            tokenCSRFForm,
            kodeCabang,
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
