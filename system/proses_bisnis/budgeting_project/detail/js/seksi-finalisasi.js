function seksiFormFinalisasi() {
    const kodeBudgetingProject = $("#kodeBudgetingProject").val();
    $.ajax({
        url: "seksi_finalisasi/form-finalisasi.php",
        type: "post",
        data: {
            kodeBudgetingProject: kodeBudgetingProject,
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

function prosesFinalisasi() {
    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();
    const kodeBudgetingProject = $("input[name=kodeBudgetingProject]").val();

    $.ajax({
        url: "seksi_finalisasi/proses-finalisasi.php",
        type: "post",
        data: {
            flag: "finalisasi",
            tokenCSRFForm,
            kodeBudgetingProject,
        },
        dataType: "json",

        beforeSend: function () {},

        success: function (data) {
            const { status, pesan } = data;
            notifikasi(status, pesan);

            if (status) {
                setTimeout(() => {
                    window.location.href = "../";
                }, 500);
            }
        },
    });
}
