function seksiFormTim(idBudgetingProjectTim) {
    const kodeBudgetingProject = $("#kodeBudgetingProject").val();
    $.ajax({
        url: "seksi_tim/form-tim.php",
        type: "post",
        data: {
            kodeBudgetingProject: kodeBudgetingProject,
            idBudgetingProjectTim: idBudgetingProjectTim,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailBudgetingProject").html(data);
            $(".loader-custom").hide();

            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesTim() {
    const formTim = document.getElementById("formTim");
    const dataForm = new FormData(formTim);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_tim/proses-tim.php",
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

                seksiFormTim("");
            },
        });
    }
}


function deleteTim(idBudgetingProjectTim, token) {

	$.ajax({
		url: "seksi_tim/proses-tim.php",
		type: "post",
		data: {
			tokenCSRFForm: token,
			idBudgetingProjectTim:idBudgetingProjectTim,
			flag: "delete",
		},
		dataType: "json",
		success: function (data) {
			//console.log(data);
			const { status, pesan } = data;
			notifikasi(status, pesan);
			seksiFormTim("");
		},
	});
}