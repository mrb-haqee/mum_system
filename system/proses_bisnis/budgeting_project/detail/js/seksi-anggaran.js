function seksiFormAnggaran(idBudgetingProjectAnggaran) {
    const kodeBudgetingProject = $("#kodeBudgetingProject").val();
    $.ajax({
        url: "seksi_anggaran/form-anggaran.php",
        type: "post",
        data: {
            kodeBudgetingProject: kodeBudgetingProject,
            idBudgetingProjectAnggaran:idBudgetingProjectAnggaran
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

function prosesAnggaran() {
    const formAnggaran = document.getElementById("formAnggaran");
    const dataForm = new FormData(formAnggaran);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_anggaran/proses-anggaran.php",
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

                seksiFormAnggaran("");
            },
        });
    }
}