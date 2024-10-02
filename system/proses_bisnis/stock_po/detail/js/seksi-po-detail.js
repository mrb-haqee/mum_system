
function seksiFormPODetail(idPODetail='') {
    const kodePO = $("#kodePO").val();

    $.ajax({
        url: "seksi_po_detail/form-po-detail.php",
        type: "post",
        data: {
            kodePO,
            idPODetail
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailPO").html(data);
            $(".loader-custom").hide();

            $("select.selectpicker").selectpicker();

        },
    });
}

function showSubTotal() {
	const hargaSatuan = rupiahToNumber($('#hargaSatuan').val());
	const qty = $("#qty").val();
	const subTotal = qty * hargaSatuan;
    
    $('#subTotal').val(numberToRupiah(subTotal));
}

function showBarang() {
    let idInventory = $('#idInventory option:selected')

	const hargaSatuan =  idInventory.data('harga-satuan');
	const satuan =  idInventory.data('satuan-barang');

    // console.log(satuan, hargaSatuan);

    $('#hargaSatuan').val(hargaSatuan?numberToRupiah(hargaSatuan):0);
    $('#satuan').val(satuan)
}

function prosesPODetail() {
    const formPODetail = document.getElementById("formPODetail");
    const dataForm = new FormData(formPODetail);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_po_detail/proses-po-detail.php",
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

                seksiFormPODetail();
            },
        });
    }
}

function prosesPOPembayaran() {
    const formPOPembayaran = document.getElementById("formPOPembayaran");
    const dataForm = new FormData(formPOPembayaran);
    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_po_detail/proses-po-detail.php",
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

                seksiFormPODetail();
            },
        });
    }
}

function deletePODetail(idPODetail, kodePO, token) {
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
                url: "seksi_po_detail/proses-po-detail.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idPODetail,
                    flag: "delete",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    seksiFormPODetail()
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}
