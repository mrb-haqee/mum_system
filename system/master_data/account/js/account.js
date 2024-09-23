document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarAccount();
    }
});

function cariAccount() {
    const kataKunciData = $("#kataKunciData").val();

    if (kataKunciData) {
        $.ajax({
            url: "data-daftar-account.php",
            type: "post",
            data: {
                kataKunciData: kataKunciData,
                flag: "cari",
            },
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftarAccount").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftarAccount() {
    $.ajax({
        url: "data-daftar-account.php",
        type: "post",
        data: {
            flag: "daftar",
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            //console.log(data);
            $("#dataDaftarAccount").html(data);
            $(".overlay").hide();
        },
    });
}



function getSubAccount(kodeAccount) {

    const $tr = $(`tr[data-id=${kodeAccount}]`);

    if ($tr.hasClass("d-none")) {
        $tr.removeClass("d-none");

        if ($(`#detail_${kodeAccount}`).length === 0) {
            $.ajax({
                url: "detail-sub-account.php",
                type: "POST",
                data: {
                    kodeAccount,
                },
                success: function (data, status) {
                    console.log(data);
                    
                    // $tr.html(data);
                },
            });
        }
    } else {
        $tr.addClass("d-none");
    }
}

function konfirmasiHapusAccount(id, token) {
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
                url: "proses-account.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idAccount: id,
                    flag: "delete",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    dataDaftarAccount();
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
