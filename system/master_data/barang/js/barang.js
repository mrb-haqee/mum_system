document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarBarang();
    }
});

function dataCariDaftarBarang() {
    const search = $("#search").val();

    if (search) {
        $.ajax({
            url: "data-daftar-barang.php",
            type: "post",
            data: {
                search: search,
                flag: "cari",
            },  
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftarBarang").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftarBarang() {
    $.ajax({
        url: "data-daftar-barang.php",
        type: "post",
        data: {
            flag: "daftar",
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            $("#dataDaftarBarang").html(data);
            $(".overlay").hide();
        },
    });
}

function konfirmasiBatalBarang(kode, token) {
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
                url: "proses-barang.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    kodeBarang: kode,
                    flag: "delete",
                },
                dataType: "json",
                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    dataDaftarBarang();
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
