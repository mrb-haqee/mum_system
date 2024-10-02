function seksiFormFinalisasi() {
    let kodeBarang = $("#kodeBarang").val();
    let urlRedirect = $("#urlRedirect").val()?$("#urlRedirect").val():'';
    $.ajax({
        url: "seksi_finalisasi/form-finalisasi.php",
        type: "post",
        data: {
            kodeBarang: kodeBarang,
            urlRedirect: urlRedirect,
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

function prosesFinalisasi(redirect='') {
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
                if(redirect!=''){
                    setTimeout(() => {
                        window.location.href = redirect;
                    }, 500);
                }else{
                    setTimeout(() => {
                        window.location.href = "../";
                    }, 500);
                }
            }
        },
    });
}
