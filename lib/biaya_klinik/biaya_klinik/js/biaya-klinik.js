document.addEventListener("readystatechange", function (event) {
	if (document.readyState === "complete") {
		dataDaftarBiayaKlinik();
	}
});



$(document).ready(function () {
    $("#periode").daterangepicker({
        buttonClasses: " btn",
        applyClass: "btn-primary",
        cancelClass: "btn-secondary",
        locale: {
            format: "YYYY-MM-DD",
        },
    });

	$(document).on("input", "input[type=file].dropify", function (e) {
        const name = $(this).attr("name");
        $(".btn-group[data-name=" + name + "] button:nth-child(1)").click();
    });
});

function dataDaftarBiayaKlinik() {
	const periode = $("#periode").val();

	$.ajax({
		url: "daftar-biaya-klinik.php",
		type: "post",
		data: {
			periode,
		},
		beforeSend: function () {
			$(".overlay").show();
		},
		success: function (data, status) {
			$("#boxDaftarBiayaKlinik").html(data);
			$(".overlay").hide();
		},
	});
}

function getFormBiayaKlinik(idBiayaKlinik = "") {
	$("#modalFormBiayaKlinik").modal("show");
	$.ajax({
		url: "form-biaya-klinik.php",
		type: "post",
		data: {
			idBiayaKlinik,
		},
		beforeSend: function () {},
		success: function (data, status) {
			$("#boxFormBiayaKlinik").html(data);
			$("select.selectpicker").selectpicker();
			$(".dropify").dropify({
                messages: {
                    default: "Drag and drop a file here or click ( FILE < 1 MB [JPG,JPEG,PNG])",
                    replace: "Drag and drop or click to replace ( FILE < 1 MB [JPG,JPEG,PNG])",
                    remove: "Remove",
                    error: "Ooops, something wrong happended.",
                },
            });
            $(".dropify-clear").remove();
			getFormBiayaDetail();

		},
	});
}

function prosesFile(btn, name, flag) {
    const tokenCSRFForm = $(`input[name=tokenCSRFForm]`).val();

    const element = $("input[name=" + name + "]");
    const elementHeight = element.data("height");

    const noForm = btn.data("no-form");
    const folder = btn.data("folder");
    const kode = btn.data("kode");
    const ajaxURL = atob(btn.data("proses")) + ".php";

    const dataForm = new FormData();

    dataForm.append("flag", flag);
    dataForm.append("tokenCSRFForm", tokenCSRFForm);
    dataForm.append("noForm", noForm);
    dataForm.append("kodeFile", kode);
    dataForm.append("folder", folder);

    if (flag === "uploadFile") {
        const file = element[0].files[0] === undefined ? "" : element[0].files[0];

        dataForm.append("htmlName", name);
        dataForm.append(name, file);
    } else if (flag == "deleteFile") {
        btn.removeData();
    }

    const validasi = formValidation(dataForm);
    $(".btn-group[data-name=" + name + "] button").attr("disabled", "disabled");
    // btn.attr("disabled", "disabled");

    if (validasi === true) {
        $.ajax({
            url: ajaxURL,
            type: "POST",
            enctype: "multipart/form-data",
            processData: false,
            contentType: false,
            data: dataForm,
            dataType: "json",
            beforeSend: function () {
                const progressBarElement = `<div class="col-md-12" id="boxProgressBar" data-progress="${name}">
                                                <div class="progress" style="height : 14px">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>`;

                $(progressBarElement).insertBefore("div[data-box=" + name + "]");
            },
            success: function (data) {
                const { status, pesan, more } = data;
                const { kodeFile, fileInputPreview, filePreview, allowedType } = more;

                const textAllowedType = allowedType.join(" ");

                $("div[data-progress=" + name + "]").remove();

                if (flag === "uploadFile" && status === true) {
                    if ($(".btn-group[data-name=" + name + "] button:nth-child(1)").hasClass("btn-success")) {
                        $(".btn-group[data-name=" + name + "] button:nth-child(1)")
                            .removeClass("btn-success")
                            .addClass("btn-secondary");
                    }
                } else if (flag === "deleteFile" && status === true) {
                    if ($(".btn-group[data-name=" + name + "] button:nth-child(1)").hasClass("btn-secondary")) {
                        $(".btn-group[data-name=" + name + "] button:nth-child(1)")
                            .removeClass("btn-secondary")
                            .addClass("btn-success");
                    }
                }

                notifikasi(status, pesan);

                const boxElement = $("div[data-box=" + name + "]");
                let inputElement = "";

                if (status === true) {
                    inputElement = `<input type="file" class="dropify" name="${name}" data-default-file="${fileInputPreview}" data-height="${elementHeight}" data-allowed-file-extensions="${textAllowedType}" data-max-file-size="1M" />`;
                } else {
                    inputElement = `<input type="file" class="dropify" name="${name}" data-default-file="" data-height="${elementHeight}" data-allowed-file-extensions="${textAllowedType}" data-max-file-size="1M"/>`;
                }

                boxElement.empty().html(inputElement);

                $(".btn-group[data-name=" + name + "] button[data-url]")
                    .removeAttr("data-url")
                    .attr("data-url", fileInputPreview);
                $(".btn-group[data-name=" + name + "] .btn-preview")
                    .removeAttr("href")
                    .attr("href", filePreview);
                $(".btn-group[data-name=" + name + "] button[data-kode]")
                    .removeAttr("data-kode")
                    .attr("data-kode", kodeFile);

                $(".dropify").dropify({
                    messages: {
                        default: `Drag and drop a file here or click ( FILE < 1 MB [${String(textAllowedType).toUpperCase()}])`,
                        replace: `Drag and drop or click to replace ( FILE < 1 MB [${String(textAllowedType).toUpperCase()}])`,
                        remove: "Remove",
                        error: "Ooops, something wrong happended.",
                    },
                });
                $(".dropify-clear").remove();

                $(".btn-group[data-name=" + name + "] button").removeAttr("disabled");
            },
        });
    } else {
        notifikasi(false, "Proses Gagal, Form Belum Terisi Dengan Lengkap");
        $(".btn-group[data-name=" + name + "] button").removeAttr("disabled");
    }
}

function getFormBiayaDetail(idBiayaKlinikDetail = "") {
	const flag = $("#flag").val();
	const kodeBiaya = $("#kodeBiaya").val();
    $.ajax({
        url: "form-biaya-detail.php",
        type: "post",
        data: {
			flag,
            kodeBiaya,
            idBiayaKlinikDetail,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#boxFormBiayaDetail").html(data);
            $("select.select2").select2();
        },
    });
}

function showSubTotal() {
	const hargaSatuan = rupiahToNumber($('#hargaSatuan').val());
	const qty = $("#qty").val();
	const subTotal = qty * hargaSatuan;
    
    $('#subTotal').val(numberToRupiah(subTotal));
}


function prosesBiayaDetail() {
	const formBiayaDetail = document.getElementById("formBiayaDetail");
	const dataForm = new FormData(formBiayaDetail);

	const validasi = formValidation(dataForm);

	if (validasi) {
		$.ajax({
			url: "proses-biaya-klinik.php",
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
					getFormBiayaDetail(dataForm.get("kodeBiaya"));			
				}
				notifikasi(status, pesan);
			},
		});
	}
}

function prosesBiaya() {
	const formBiayaKlinik = document.getElementById("formBiayaKlinik");
	const dataForm = new FormData(formBiayaKlinik);

	const validasi = formValidation(dataForm);

	if (validasi) {
		$.ajax({
			url: "proses-biaya-klinik.php",
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
					dataDaftarBiayaKlinik();
					getFormBiayaKlinik();
				}

				notifikasi(status, pesan);
			},
		});
	}
}

function deleteBiaya(id, token) {
	Swal.fire({
		title: "Apakah Anda Yakin ?",
		text: "Setelah dibatalkan, proses tidak dapat diulangi!",
		icon: "warning",
		showCancelButton: true,
		confirmButtonText: "Ya!",
		cancelButtonText: "Tidak!",
	}).then(function (result) {
		if (result.value) {
			$.ajax({
				url: "proses-biaya-klinik.php",
				type: "post",
				data: {
					tokenCSRFForm: token,
					idBiayaKlinik: id,
					flag: "delete",
				},
				dataType: "json",
				success: function (data) {
					//console.log(data);
					const { status, pesan } = data;

					dataDaftarBiayaKlinik();
					notifikasi(status, pesan);
				},
			});
		} else if (result.dismiss === "cancel") {
			Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
		}
	});
}

function deleteBiayaDetail(idBiayaKlinikDetail, kodeBiaya, token) {
	$.ajax({
		url: "proses-biaya-klinik.php",
		type: "post",
		data: {
			tokenCSRFForm: token,
			idBiayaKlinikDetail,
			kodeBiaya,
			flag: "deleteDetail",
		},
		dataType: "json",
		success: function (data) {
			//console.log(data);
			const { status, pesan } = data;
			getFormBiayaDetail(kodeBiaya);	
			notifikasi(status, pesan);
		},
	});
}

function notifikasi(status, pesan) {
	if (status === true) {
		toastr.success(pesan);
	} else {
		toastr.error(pesan);
	}
}
