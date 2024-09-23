$(function () {
    $(document).on("input", "input[type=file].dropify", function (e) {
        const name = $(this).attr("name");
        $(".btn-group[data-name=" + name + "] button:nth-child(1)").click();
    });
});

function seksiFormInformasi() {
    const kodeBudgetingProject = $("#kodeBudgetingProject").val();

    $.ajax({
        url: "seksi_informasi/form-informasi.php",
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

            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesBudgetingProject() {
    const formBudgetingProject = document.getElementById("formBudgetingProject");
    const dataForm = new FormData(formBudgetingProject);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_informasi/proses-informasi.php",
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

                seksiFormInformasi();
            },
        });
    }
}
