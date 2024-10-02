document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftar();
        // getFormStockAwalBarang()
    }
});

$(function () {
    $("select.select2").select2();
});

function dataCariDaftar() {
    const kataKunciData = $("#kataKunciData").val();

    if (kataKunciData) {
        $.ajax({
            url: "data-daftar.php",
            type: "post",
            data: {
                kataKunciData: kataKunciData,
                flagData: "cari",
            },
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftar").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftar() {
    $.ajax({
        url: "data-daftar.php",
        type: "post",
        data: {
            flagData: "daftar",
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            $("#dataDaftar").html(data);
            $(".overlay").hide();
        },
    });
}

function getFormStockAwalBarang(idStockAwal = "") {
    $("#modalFormStockAwalBarang").modal("show");

    $.ajax({
        url: "form-stock-awal.php",
        type: "post",
        data: {
            idStockAwal: idStockAwal,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#boxFormStockAwalBarang").html(data);
            $("select.select2").select2();

        },
    });
}

function showBarang() {
	const satuan =  $('#idInventory option:selected').data('satuan-barang');
    $('#satuan').val(satuan)
}

function prosesStockAwalBarang() {
    const formStock = document.getElementById("formStock");
    const dataForm = new FormData(formStock);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "proses.php",
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

                if (status) {
                    dataDaftar();

                    if (dataForm.get("flag") === "tambah") {
                        getFormStockAwalBarang();
                    } else {
                        $("#modalFormStockAwalBarang").modal("hide");
                    }
                }
            },
        });
    }
}

function konfirmasiBatal(id, token) {
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
                url: "proses.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idStockAwal: id,
                    flag: "delete",
                },

                success: function (data, status) {
                    //console.log(data);
                    let dataJSON = JSON.parse(data);
                    dataDaftar();
                    notifikasi(dataJSON);
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}

function notifikasi(status, pesan) {
    if (status) {
        toastr.success(pesan);
    } else {
        toastr.error(pesan);
    }
}

