function formatInputRupiah(selector) {
    const value = $(selector).val();

    let nominal = "0",
        decimal = "0";

    let isDecimal;

    if (/[,]/.test(value)) {
        [nominal, decimal] = String(value).split(",");
        isDecimal = true;
    } else {
        nominal = value;
        isDecimal = false;
    }

    // BAGIAN NOMINAL

    let unformatNominal = String(nominal).replace(/[.]/g, "");
    let type = "";

    if (/(^-)|(^0-)/g.test(unformatNominal)) {
        type = "negative";
    } else {
        type = "positive";
    }

    const filteredNominal = unformatNominal.replace(/[\D]/g, "");

    // BAGIAN DESIMAL
    let output;
    if (isDecimal) {
        let filteredDecimal = decimal.replace(/[\D]/g, "");

        if (filteredDecimal !== "0") {
            filteredDecimal = filteredDecimal.replace(/0+$/g, "");
        }

        if (filteredDecimal === "") {
            output = new Intl.NumberFormat("de-DE").format(filteredNominal) + ",";
        } else {
            output = new Intl.NumberFormat("de-DE").format(filteredNominal) + "," + filteredDecimal;
        }
    } else {
        output = new Intl.NumberFormat("de-DE").format(filteredNominal);
    }

    // CONCAT NOMINAL & DESIMAL

    if (type == "negative") {
        output = "-" + output;
    }

    $(selector).val(output);
}

function rupiahToNumber(value) {
    const nominal = value || "0";
    let integer, desimal;

    if (/[\,]/g.test(value)) {
        [integer, desimal] = String(nominal).split(",");
        integer = integer.split(".").join("");

        return parseFloat(integer + "." + desimal);
    } else {
        integer = nominal.split(".").join("");

        return parseInt(integer);
    }
}

function numberToRupiah(value) {
    if (/[\.]/g.test(value)) {
        const [nominal, decimal] = String(value).split(".");
        return new Intl.NumberFormat("de-DE").format(nominal) + "," + decimal;
    } else {
        return new Intl.NumberFormat("de-DE").format(value);
    }
}

$(function () {
    $(document).on("keyup", "input[type=text][data-format-rupiah=active]", function (ev) {
        formatInputRupiah("#" + $(this).attr("id"));
    });
});
