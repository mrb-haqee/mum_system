const editors_pemeriksaan = {};

function seksiFormPemeriksaan() {
    let kodeAntrian = $("#kodeAntrian").val();
    let kodeRM = $("#kodeRM").val();

    $.ajax({
        url: "seksi_pemeriksaan/form-pemeriksaan.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeRM: kodeRM,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailPembayaran").html(data);
            $(".loader-custom").hide();

            $("select.selectpicker").selectpicker();

            $("textarea[data-editor=active]").each((index, element) => {
                ClassicEditor.create(document.querySelector("#" + element.id))
                    .then((editor) => {
                        editors_pemeriksaan[element.id] = editor;
                    })
                    .catch((error) => {
                        console.error(error);
                    });
            });
        },
    });
}
