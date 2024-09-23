function seksiFormInformasi() {
    let kodeBarang = $("#kodeBarang").val();
    $.ajax({
        url: "seksi_informasi/form-informasi.php",
        type: "post",
        data: {
            kodeBarang: kodeBarang,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            //console.log(data);
            $("#formDetailInventoryBarang").html(data);

            $(".loader-custom").hide();

            $("select.selectpicker").selectpicker();
            $("select.select2").select2();
        },
    });
}

function prosesBarang() {
    const formBarang = document.getElementById("formBarang");
    const dataForm = new FormData(formBarang);

    const validasi = formValidation(dataForm, []);

    if (validasi) {
        $.ajax({
            url: "seksi_informasi/proses-barang.php",
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

                seksiFormInformasi();
            },
        });
    }
}
