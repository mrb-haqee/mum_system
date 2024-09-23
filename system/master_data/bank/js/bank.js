document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarBank();
    }
});

function cariBank() {
    const kataKunciData = $("#kataKunciData").val();

    if (kataKunciData) {
        $.ajax({
            url: "data-daftar-bank.php",
            type: "post",
            data: {
                kataKunciData: kataKunciData,
                flag: "cari",
            },
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftarBank").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftarBank() {
    $.ajax({
        url: "data-daftar-bank.php",
        type: "post",
        data: {
            flag: "daftar",
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            //console.log(data);
            $("#dataDaftarBank").html(data);
            $(".overlay").hide();
        },
    });
}

function konfirmasiBatalBank(id, token) {
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
                url: "proses-bank.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idBank: id,
                    flag: "delete",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    dataDaftarBank();
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
