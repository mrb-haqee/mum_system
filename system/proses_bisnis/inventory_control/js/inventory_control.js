document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarInventory();
        
    }
});
$("#periode").daterangepicker({
    buttonClasses: " btn",
    applyClass: "btn-primary",
    cancelClass: "btn-secondary",
    locale: {
        format: "YYYY-MM-DD",
    },
});

function dataDaftarInventory() {
    const periode = $("#periode").val();
    const tipeInventory = $("#tipeInventory").val();
    $.ajax({
        url: "data-daftar.php",
        type: "post",
        data: {
            flag: "daftar",
            periode,
            tipeInventory
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            //console.log(data);
            $("select.selectpicker").selectpicker();
            $("#dataDaftarInventory").html(data);
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
