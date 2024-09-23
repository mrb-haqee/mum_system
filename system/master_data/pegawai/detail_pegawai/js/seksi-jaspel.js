function seksiFormJaspel() {
    let kodePegawai = $("#kodePegawai").val();
    $.ajax({
        url: "seksi_jaspel/form-jaspel.php",
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

            selectPersentase(kodePegawai);
        },
    });
}

function selectPersentase(kodePegawai = "") {
    const idJabatan = $("#idJabatan").val();
    $.ajax({
        url: "seksi_jaspel/select-persentase.php",
        type: "post",
        data: {
            kodePegawai: kodePegawai,
            idJabatan: idJabatan,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data) {
            $("#boxSelectPersentase").html(data);
            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesJaspel() {
    const formElement = document.getElementById("formJaspel");
    const dataForm = new FormData(formElement);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_jaspel/proses-jaspel.php",
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

                seksiFormJaspel();
            },
        });
    }
}
