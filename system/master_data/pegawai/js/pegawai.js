document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarPegawai();
    }
});

function dataCariDaftarPegawai() {
    const kataKunciData = $("#kataKunciData").val();

    if (kataKunciData) {
        $.ajax({
            url: "datadaftarpegawai.php",
            type: "post",
            data: {
                kataKunciData: kataKunciData,
                flagData: "cari",
            },
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftarPegawai").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftarPegawai() {
    $.ajax({
        url: "datadaftarpegawai.php",
        type: "post",
        data: {
            flagData: "daftar",
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            $("#dataDaftarPegawai").html(data);

            $(".overlay").hide();
        },
    });
}

function getDetailPegawai(idPegawai, IDcontainer) {
    $.ajax({
        url: "detailpegawai.php",
        type: "post",
        data: {
            flagData: "daftar",
            idPegawai,
        },
        success: function (data, status) {
            $(IDcontainer).html(data);
        },
    });
}

function konfirmasiBatalPegawai(id, token) {
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
                url: "prosespegawai.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idPegawai: id,
                    flag: "delete",
                },

                success: function (data, status) {
                    //console.log(data);
                    let dataJSON = JSON.parse(data);
                    notifikasi(dataJSON);
                    dataDaftarPegawai();
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}
