document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        btnExaminationTab("btn-detail-purchasing-tab", "btn-danger", "btn-light-danger");
        btnExaminationTab("btn-status-type-tab", "btn-danger", "btn-light-danger");
        // getFormPurchasing('MUM/purchasing/1/000000003','')
        boxPurchasing()
    }
});

function EditBtn(){
    $('.btn-detail-purchasing-tab').each(function() {
        if ($(this).hasClass('btn-light-danger')) {
            $(this).removeClass('btn-light-danger').addClass('btn-danger');
        } else if ($(this).hasClass('btn-danger')) {
            $(this).removeClass('btn-danger').addClass('btn-light-danger');
        }
    });
}


function boxPurchasing() {
    $.ajax({
        url: "daftar-purchasing.php",
        type: "post",
        data: {},
        success: function (data, status) {
            $("#boxPurchasing").html(data);
            $("#periodePurchasing").daterangepicker({
                buttonClasses: " btn",
                applyClass: "btn-primary",
                cancelClass: "btn-secondary",
                locale: {
                    format: "YYYY-MM-DD",
                },
            });

            dataPurchasing();
        },
    });
}

function dataPurchasing(status) {
    const rentang = $("#periodePurchasing").val();
    const statusPersetujuan = status || $(".btn-status-type-tab.btn-danger").data("status");

    $.ajax({
        url: "data-purchasing.php",
        type: "post",
        data: {
            flag: "daftar",
            rentang,
            statusPersetujuan,
        },
        success: function (data) {
            $("#boxDataPurchasing").html(data);
        },
    });
}

function getFormPurchasing(kodePurchasing) {
    $.ajax({
        url: "form-purchasing.php",
        type: "POST",
        data: {
            kodePurchasing,
        },
        success: function (data, status) {
            $("#boxPurchasing").html(data);
            $("select.selectpicker").selectpicker();
            getFormPurchasingDetail('')
        },
    });
}
function getFormPurchasingDetail(idPurchasingDetail) {
    const flag = $('#flag').val()
    const kodePurchasing = $('#kodePurchasing').val()
    
    $.ajax({
        url: "form-purchasing-detail.php",
        type: "POST",
        data: {
            flag,
            kodePurchasing,
            idPurchasingDetail
        },
        success: function (data, status) {
            $("#boxPurchasingDetail").html(data);
            $("select.selectpicker").selectpicker();
        },
    });
}
function showBarang() {
	const hargaBarang =  $('#idBarang option:selected').data('harga-barang');
	const satuan =  $('#idBarang option:selected').data('satuan-barang');

    $('#hargaBarang').val(hargaBarang?numberToRupiah(hargaBarang):0);
    $('#satuanBarang').text(satuan?satuan:'');
    
}

function showSubTotal() {
	const hargaBarang = rupiahToNumber($('#hargaBarang').val());
	const qty = $("#qty").val();
	const subTotal = qty * hargaBarang;
    
    $('#subTotal').val(numberToRupiah(subTotal));
}


function prosesPurchasing() {
	const formPurchasing = document.getElementById("formPurchasing");
    
	const dataForm = new FormData(formPurchasing);

	const validasi = formValidation(dataForm);

	if (validasi) {
		$.ajax({
			url: "proses-purchasing.php",
			type: "post",
			enctype: "multipart/form-data",
			processData: false,
			contentType: false,
			data: dataForm,
			dataType: "json",

			beforeSend: function () {},

			success: function (data) {
				const { status, pesan } = data;

				if (status) {
					getFormPurchasing(dataForm.get("kodePurchasing"));			
				}
				notifikasi(status, pesan);
			},
		});
	}
}

function prosesPurchasingDetail() {
	const formPurchasingDetail = document.getElementById("formPurchasingDetail");
    
	const dataForm = new FormData(formPurchasingDetail);

	const validasi = formValidation(dataForm);

	if (validasi) {
		$.ajax({
			url: "proses-purchasing.php",
			type: "post",
			enctype: "multipart/form-data",
			processData: false,
			contentType: false,
			data: dataForm,
			dataType: "json",

			beforeSend: function () {},

			success: function (data) {
				const { status, pesan } = data;

				if (status) {
					getFormPurchasingDetail(dataForm.get("kodePurchasing"),'');			
				}
				notifikasi(status, pesan);
			},
		});
	}
}
function prosesPurchasingDiscountPPN() {
	const formPurchasingDiscountPPN = document.getElementById("formPurchasingDiscountPPN");
    
	const dataForm = new FormData(formPurchasingDiscountPPN);

	const validasi = formValidation(dataForm);

	if (validasi) {
		$.ajax({
			url: "proses-purchasing.php",
			type: "post",
			enctype: "multipart/form-data",
			processData: false,
			contentType: false,
			data: dataForm,
			dataType: "json",

			beforeSend: function () {},

			success: function (data) {
				const { status, pesan } = data;

				if (status) {
					getFormPurchasingDetail(dataForm.get("kodePurchasing"),'');			
				}
				notifikasi(status, pesan);
			},
		});
	}
}

function deletePurchasing(kodePurchasing, token) {
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
                url: "proses-purchasing.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    kodePurchasing,
                    flag: "delete",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    getFormPurchasingDetail()
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}
function deletePurchasingDetail(idPurchasingDetail, kodePurchasing, token) {
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
                url: "proses-purchasing.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idPurchasingDetail,
                    flag: "deleteDetail",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    dataPurchasing(kodePurchasing,'');
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
