document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarPettyCash();
    }
});

function cariPettyCash() {
    const kataKunciData = $("#kataKunciData").val();

    if (kataKunciData) {
        $.ajax({
            url: "data-daftar-petty-cash.php",
            type: "post",
            data: {
                kataKunciData: kataKunciData,
                flag: "cari",
            },
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftarPettyCash").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftarPettyCash() {
    $.ajax({
        url: "data-daftar-petty-cash.php",
        type: "post",
        data: {
            flag: "daftar",
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            //console.log(data);
            $("#dataDaftarPettyCash").html(data);
            $(".overlay").hide();
        },
    });
}

function konfirmasiBatalPettyCash(id, token) {
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
                url: "proses-petty-cash.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idPettyCash: id,
                    flag: "delete",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    dataDaftarPettyCash();
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
