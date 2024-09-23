document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarTujuanTransfer();
    }
});

function cariTujuanTransfer() {
    const kataKunciData = $("#kataKunciData").val();

    if (kataKunciData) {
        $.ajax({
            url: "data-daftar-tujuan-transfer.php",
            type: "post",
            data: {
                kataKunciData: kataKunciData,
                flag: "cari",
            },
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftarTujuanTransfer").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftarTujuanTransfer() {
    $.ajax({
        url: "data-daftar-tujuan-transfer.php",
        type: "post",
        data: {
            flag: "daftar",
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            //console.log(data);
            $("#dataDaftarTujuanTransfer").html(data);
            $(".overlay").hide();
        },
    });
}

function konfirmasiBatalTujuanTransfer(id, token) {
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
                url: "proses-tujuan-transfer.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idTujuanTransfer: id,
                    flag: "delete",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    dataDaftarTujuanTransfer();
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
