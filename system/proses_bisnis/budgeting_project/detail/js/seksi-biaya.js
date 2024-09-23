function seksiFormBiaya(idBudgetingProjectBiaya) {
    const kodeBudgetingProject = $("#kodeBudgetingProject").val();
    $.ajax({
        url: "seksi_biaya/form-biaya.php",
        type: "post",
        data: {
            kodeBudgetingProject: kodeBudgetingProject,
            idBudgetingProjectBiaya:idBudgetingProjectBiaya,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailBudgetingProject").html(data);
            $(".loader-custom").hide();
        },
    });
}

function prosesBiaya() {
    const formBiaya = document.getElementById("formBiaya");
    const dataForm = new FormData(formBiaya);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_biaya/proses-biaya.php",
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

                seksiFormBiaya("");
            },
        });
    }
}


function showSubTotal() {
	const hargaSatuan = rupiahToNumber($('#hargaSatuan').val());
	const qty = $("#qty").val();
	const subTotal = qty * hargaSatuan;
    
    $('#subTotal').val(numberToRupiah(subTotal));
}


function deleteBiaya(idBudgetingProjectBiaya, token) {

	$.ajax({
		url: "seksi_biaya/proses-biaya.php",
		type: "post",
		data: {
			tokenCSRFForm: token,
			idBudgetingProjectBiaya:idBudgetingProjectBiaya,
			flag: "delete",
		},
		dataType: "json",
		success: function (data) {
			//console.log(data);
			const { status, pesan } = data;
			notifikasi(status, pesan);
			seksiFormBiaya("");
		},
	});
}