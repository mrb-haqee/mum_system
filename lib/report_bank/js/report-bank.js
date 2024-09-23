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
    const idBank = $("#idBank").val();

    const currency = $("#currency").val();

    $.ajax({
        url: "daftar-report-bank.php",
        type: "post",
        data: {
            periode,
            idBank,
            currency,
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

function selectCurrency() {
    const idBank = $("#idBank").val();

    $.ajax({
        url: "currency.php",
        type: "post",
        data: {
            periode,
            idBank,
        },
        success: function (data, status) {
            $("#boxCurrency").html(data);
            $("select.select2").select2();
        },
    });
}
