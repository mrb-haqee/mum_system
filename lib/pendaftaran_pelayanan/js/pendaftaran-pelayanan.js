document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        btnExaminationTab("btn-detail-register-tab", "btn-primary", "btn-light-primary");
        btnExaminationTab("btn-admission-type-tab", "btn-primary", "btn-light-primary");

        boxPendaftaran();
    }
});

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

    $(document).on("change", "#searchPasienDaftar", cariPasienDaftar);

    $(document).on("change", "#searchPasienForm", cariPasienForm);

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

function cariPasienDaftar() {
    const search = $("#searchPasienDaftar").val();
    const rentang = $("#periodePendaftaran").val();

    const idAdmisi = $(".btn-admission-type-tab.btn-primary").data("id");

    $.ajax({
        url: "data-pendaftaran.php",
        type: "post",
        data: {
            flag: "search",
            rentang: rentang,
            idAdmisi,
            search,
        },
        success: function (data, status) {
            $("#boxDataPendaftaran").html(data);
        },
    });
}

function cariPasienForm() {
    const search = $("#searchPasienForm").val();
    const idAdmisi = $(".btn-admission-type-tab.btn-primary").data("id");

    $.ajax({
        url: "cari-pasien.php",
        type: "post",
        data: {
            search: search,
            idAdmisi,
        },
        success: function (data, status) {
            $("#boxResultPasien").html(data);
            $("[data-toggle=tooltip]").tooltip();
        },
    });
}

function boxPendaftaran() {
    $.ajax({
        url: "daftar-pendaftaran.php",
        type: "post",
        data: {},
        success: function (data, status) {
            $("#boxPendaftaranPelayanan").html(data);

            const dateRangeElement = $("input[type=text][data-date-range=true]");

            dateRangeElement.daterangepicker({
                buttonClasses: "btn",
                applyClass: "btn-primary",
                cancelClass: "btn-secondary",
                locale: {
                    format: "YYYY-MM-DD",
                },
            });

            dataPendaftaran();
        },
    });
}

function dataPendaftaran(paramAdmisi) {
    const rentang = $("#periodePendaftaran").val();
    const idAdmisi = paramAdmisi || $(".btn-admission-type-tab.btn-primary").data("id");

    $.ajax({
        url: "data-pendaftaran.php",
        type: "post",
        data: {
            flag: "daftar",
            rentang: rentang,
            idAdmisi,
        },
        success: function (data) {
            $("#boxDataPendaftaran").html(data);
        },
    });
}

function getFormPendaftaran(form) {
    $.ajax({
        url: "form-pasien.php",
        type: "POST",
        data: {
            flag: form,
        },
        success: function (data, status) {
            $("#boxPendaftaranPelayanan").html(data);
            $("select.selectpicker").selectpicker();

            selectWilayah();
        },
    });
}

function selectWilayah() {
    const jenisWilayah = ["provinceCode", "regencyCode", "districtCode", "villageCode"];

    const data = jenisWilayah.reduce(function (init, value) {
        init[value] = $("#" + value).val() ?? "";
        return init;
    }, {});

    $.ajax({
        url: "select-wilayah.php",
        type: "post",
        data: data,
        success: function (data, status) {
            $("#boxWilayah").html(data);
            $("select.select2").select2();
        },
    });
}

function syncSatuSehatPasien(kodeRM, tokenCSRFForm) {
    $.ajax({
        url: "sinkronisasi-satu-sehat.php",
        type: "POST",
        data: {
            flag: "syncPasien",
            kodeRM,
            tokenCSRFForm,
        },
        dataType: "json",
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data) {
            $(".overlay").hide();

            const { status, pesan } = data;
            notifikasi(status, pesan);

            cariPasienForm();
        },
    });
}

function syncSatuSehatBundle(button, kodeAntrian, tokenCSRFForm) {
    $.ajax({
        url: "sinkronisasi-satu-sehat.php",
        type: "POST",
        data: {
            flag: "bundle",
            kodeAntrian,
            tokenCSRFForm,
        },
        dataType: "json",
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data) {
            $(".overlay").hide();

            const { status, pesan } = data;
            notifikasi(status, pesan);

            if (status) {
                button.removeClass("btn-success").addClass("btn-info");
            }
        },
    });
}

async function prosesPasienBaru() {
    let formPasienBaru = document.getElementById("formPasienBaru");
    let dataForm = new FormData(formPasienBaru);

    if (String(dataForm.get("noIdentitas")).trim() === "") {
        toastr.warning("Mohon Isi No. Identitas Terlebih Dahulu !");
        return false;
    }

    const { status: statusCekIdentitas } = await $.ajax({
        url: "cek-identitas.php",
        type: "post",
        data: {
            tokenCSRFForm: dataForm.get("tokenCSRFForm"),
            noID: String(dataForm.get("noIdentitas")).trim(),
        },
        dataType: "json",
    });

    if (statusCekIdentitas === false) {
        toastr.error("No. Identitas Sudah Digunakan !");
        return false;
    }

    const idAdmisi = $(".btn-admission-type-tab.btn-primary").data("id");
    dataForm.set("idAdmisi", idAdmisi);

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
            url: "proses-pasien-baru.php",
            type: "post",
            enctype: "multipart/form-data",
            processData: false,
            contentType: false,
            data: dataForm,
            dataType: "json",

            beforeSend: function () {},

            success: function (data) {
                const { status, pesan, more } = data;
                const { query } = more;

                notifikasi(status, pesan);

                if (status) {
                    setTimeout(() => {
                        window.location.href = `detail_pendaftaran/?param=${query}`;
                    }, 1000);
                }
            },
        });
    }
}

function konfirmasiBatalPendaftaran(kode, token) {
    Swal.fire({
        title: "Apakah anda yakin?",
        text: "Setelah dibatalkan, proses tidak dapat diulangi!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya!",
        cancelButtonText: "Tidak!",
    }).then(function (result) {
        if (result.value) {
            $.ajax({
                url: "proses-pendaftaran.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    kodeAntrian: kode,
                    flag: "delete",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    dataPendaftaran();
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
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

    $.ajax({
        url: "cek-identitas.php",
        type: "post",
        data: {
            tokenCSRFForm: token,
            noID: String(noID).trim(),
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

function notifikasi(status, pesan) {
    if (status === true) {
        toastr.success(pesan);
    } else {
        toastr.error(pesan);
    }
}
