document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarStockPO();
        $("#periodePO").daterangepicker({
            buttonClasses: " btn",
            applyClass: "btn-primary",
            cancelClass: "btn-secondary",
            locale: {
                format: "YYYY-MM-DD",
            },
        });
    }
});


function cariStockPO() {
    const kataKunciData = $("#kataKunciData").val();

    if (kataKunciData) {
        $.ajax({
            url: "data-daftar-stock-po.php",
            type: "post",
            data: {
                kataKunciData: kataKunciData,
                flag: "cari",
            },
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftarStockPO").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftarStockPO() {
    const periode = $("#periodePO").val();
    const kodeVendor = $("#kodeVendor").val();
    const statusPersetujuan = $("#statusPersetujuan").val();
    const statusPO = $("#statusPO").val();
    $.ajax({
        url: "data-daftar-stock-po.php",
        type: "post",
        data: {
            flag: "daftar",
            periode,
            kodeVendor,
            statusPersetujuan,
            statusPO
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            $("select.selectpicker").selectpicker();
            $("#dataDaftarStockPO").html(data);
            $(".overlay").hide();
        },
    });
}

function konfirmasiBatalStockPO(id, token) {
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
                url: "proses-stock-po.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idPO: id,
                    flag: "delete",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    dataDaftarStockPO();
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}

function notifikasi(status, pesan) {
    if (status === true) {
        toastr.success(pesan);
    } else {
        toastr.error(pesan);
    }
}
