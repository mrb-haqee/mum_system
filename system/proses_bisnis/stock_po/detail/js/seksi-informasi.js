
function seksiFormInformasi() {
    const kodePO = $("#kodePO").val();

    $.ajax({
        url: "seksi_informasi/form-informasi.php",
        type: "post",
        data: {
            kodePO,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailPO").html(data);
            $(".loader-custom").hide();

            $("select.selectpicker").selectpicker();

        },
    });
}

function prosesPO() {
    const formPO = document.getElementById("formPO");
    const dataForm = new FormData(formPO);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_informasi/proses-informasi.php",
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
