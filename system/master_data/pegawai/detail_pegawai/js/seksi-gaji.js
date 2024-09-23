function seksiFormGaji() {
    let kodePegawai = $("#kodePegawai").val();
    $.ajax({
        url: "seksi_gaji/form-gaji.php",
        type: "post",
        data: {
            kodePegawai: kodePegawai,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $(".loader-custom").hide();
            $("#formDetailPegawai").html(data);

            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesGaji() {
    const formElement = document.getElementById("formGaji");
    const dataForm = new FormData(formElement);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_gaji/proses-gaji.php",
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

                seksiFormGaji();
            },
        });
    }
}
