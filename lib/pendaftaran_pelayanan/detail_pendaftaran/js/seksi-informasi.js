$(document).ready(function () {
    $("#periodePendaftaran").daterangepicker({
        buttonClasses: " btn",
        applyClass: "btn-primary",
        cancelClass: "btn-secondary",
        locale: {
            format: "YYYY-MM-DD",
        },
    });

    let timeoutCekNoIdentitas;
    $(document).on("keyup", "#noIdentitas", function () {
        clearTimeout(timeoutCekNoIdentitas);
        timeoutCekNoIdentitas = setTimeout(cekNoIdentitas, 350, $(this).val());
    });

    $(document).on("change", "form#formPasienBaru input[type=text]:not(#noIdentitas), form#formPasienBaru select", function (e) {
        const noIDElement = $("#noIdentitas");
        const icon = $("#icon-notif");

        if (String(noIDElement.val()).trim() === "") {
            toastr.warning("Mohon Isi No. Identitas Terlebih Dahulu !");

            const borderClass = String(noIDElement.attr("class")).match(/border-\w+/g);
            if (borderClass !== null) noIDElement.removeClass(borderClass[0]);
            noIDElement.addClass("border-warning");

            icon.attr("class", "fas fa-info-circle text-warning");
        } else {
            if (noIDElement.hasClass("border-danger")) {
                toastr.error("No. Identitas Telah Terpakai. Mohon untuk segera diganti !");
            } else {
                !noIDElement.hasClass("border-warning") || noIDElement.removeClass("border-warning");
                !icon.hasClass("text-warning") || icon.removeClass("text-warning");
            }
        }
    });
});

function seksiFormInformasi() {
    let kodeAntrian = $("#kodeAntrian").val();
    let kodeRM = $("#kodeRM").val();
    $.ajax({
        url: "seksi_informasi/form-informasi.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeRM: kodeRM,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailPendaftaran").html(data);
            $(".loader-custom").hide();

            $("select.selectpicker").selectpicker();
            $("select.select2").select2();

            selectWilayah(kodeRM);
        },
    });
}

function selectWilayah(kodeRM = "") {
    const jenisWilayah = ["provinceCode", "regencyCode", "districtCode", "villageCode"];

    const data = jenisWilayah.reduce(function (init, value) {
        init[value] = $("#" + value).val() ?? "";
        return init;
    }, {});

    data["kodeRM"] = kodeRM;

    $.ajax({
        url: "seksi_informasi/select-wilayah.php",
        type: "post",
        data: data,
        success: function (data, status) {
            $("#boxWilayah").html(data);
            $("select.select2").select2();
        },
    });
}

async function prosesInformasi() {
    const formUpdateData = document.getElementById("formUpdateData");
    const dataForm = new FormData(formUpdateData);

    if (String(dataForm.get("noIdentitas")).trim() === "") {
        toastr.warning("Mohon Isi No. Identitas Terlebih Dahulu !");
        return false;
    }

    const { status: statusCekIdentitas } = await $.ajax({
        url: "seksi_informasi/cek-identitas.php",
        type: "post",
        data: {
            tokenCSRFForm: dataForm.get("tokenCSRFForm"),
            noID: String(dataForm.get("noIdentitas")).trim(),
            kodeRM: dataForm.get("kodeRM"),
        },
        dataType: "json",
    });

    if (statusCekIdentitas === false) {
        toastr.error("No. Identitas Sudah Digunakan !");
        return false;
    }

    const validasi = formValidation(dataForm, [
        "tempatLahir",
        "alamat",
        "email",
        "postalCode",
        "provinceCode",
        "regencyCode",
        "districtCode",
        "villageCode",
        "maritalStatus",
        "language",
    ]);

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

function cekNoIdentitas(noID) {
    if (String(noID).trim() === "") {
        const noIDElement = $("#noIdentitas");
        const icon = $("#icon-notif");

        toastr.warning("Mohon Isi No. Identitas Terlebih Dahulu !");

        const borderClass = String(noIDElement.attr("class")).match(/border-\w+/g);
        if (borderClass !== null) noIDElement.removeClass(borderClass[0]);
        noIDElement.addClass("border-warning");

        icon.attr("class", "fas fa-info-circle text-warning");

        return false;
    }

    const token = $("input[name=tokenCSRFForm]").val();
    const kodeRM = $("input[name=kodeRM]").val();

    $.ajax({
        url: "seksi_informasi/cek-identitas.php",
        type: "post",
        data: {
            tokenCSRFForm: token,
            noID: String(noID).trim(),
            kodeRM: kodeRM,
        },
        dataType: "json",
        success: function (data) {
            const { status } = data;

            const icon = $("#icon-notif");
            const noIDElement = $("#noIdentitas");

            const borderClass = String(noIDElement.attr("class")).match(/border-\w+/g);
            if (borderClass !== null) noIDElement.removeClass(borderClass[0]);

            const textClass = String(icon.attr("class")).match(/text-\w+/g);
            if (textClass !== null) icon.removeClass(textClass[0]);

            if (status) {
                icon.attr("class", "fas fa-check-circle text-success");
                noIDElement.addClass("border-success");
            } else {
                icon.attr("class", "fas fa-times-circle text-danger");
                noIDElement.addClass("border-danger");

                toastr.error("No. Identitas Telah Digunakan. ");
            }
        },
    });
}
