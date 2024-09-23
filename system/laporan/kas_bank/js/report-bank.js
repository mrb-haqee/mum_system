document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarReportBank();
    }
});

$(document).ready(function () {
    $("#periode").daterangepicker({
        buttonClasses: " btn",
        applyClass: "btn-primary",
        cancelClass: "btn-secondary",
        locale: {
            format: "YYYY-MM-DD",
        },
    });

    $("select.select2").select2();
});

function dataDaftarReportBank() {
    const periode = $("#periode").val();
    const idRekening = $("#idRekening").val();


    $.ajax({
        url: "daftar-report-bank.php",
        type: "post",
        data: {
            periode: periode,
            idRekening: idRekening,
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            $("#boxDaftarReportBank").html(data);
            $(".overlay").hide();
        },
    });
}