$(function () {});

function seksiFormEDC(idTransferEDC = "") {
    const kodeTujuanTransfer = $("#kodeTujuanTransfer").val();

    $.ajax({
        url: "seksi_edc/form-edc.php",
        type: "post",
        data: {
            kodeTujuanTransfer: kodeTujuanTransfer,
            idTransferEDC,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailTujuanTransfer").html(data);
            $(".loader-custom").hide();

            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesEDC() {
    const formEDC = document.getElementById("formEDC");
    const dataForm = new FormData(formEDC);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_edc/proses-edc.php",
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

                seksiFormEDC();
            },
        });
    }
}

function deleteEDC(id, token) {
    $.ajax({
        url: "seksi_edc/proses-edc.php",
        type: "post",
        data: {
            tokenCSRFForm: token,
            idTransferEDC: id,
            flag: "delete",
        },
        dataType: "json",

        success: function (data) {
            const { status, pesan } = data;
            notifikasi(status, pesan);

            seksiFormEDC();
        },
    });
}
