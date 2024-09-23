var signPad;

async function savePad(btn) {
    const file = signPad.toDataURL();

    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();
    const kodePegawai = $("input[name=kodePegawai]").val();

    const formData = new FormData();

    formData.append("fileTTD", file);
    formData.append("tokenCSRFForm", tokenCSRFForm);
    formData.append("kodePegawai", kodePegawai);
    formData.append("flag", "saveTTD");

    const response = await $.ajax({
        url: "seksi_informasi/proses-pegawai.php",
        method: "POST",
        enctype: "multipart/form-data",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
    });

    const { status, pesan } = response;

    if (status) {
        btn.removeClass(["badge-info", "badge-primary", "badge-success"]);

        btn.addClass(["badge-success"]);
        btn.html('<i class="fas fa-check-circle pr-4"></i><strong>TERSIMPAN</strong>');

        setTimeout(() => {
            btn.addClass(["badge-info"]);
            btn.html('<i class="fas fa-upload pr-4"></i><strong>UPLOAD</strong>');
        }, 2000);
    } else {
        alert(pesan);
    }
}

function clearPad() {
    signPad.clear();
}

function seksiFormInformasi() {
    const kodePegawai = $("#kodePegawai").val();
    $.ajax({
        url: "seksi_informasi/form-informasi.php",
        type: "post",
        data: {
            kodePegawai: kodePegawai,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $(".loader-custom").hide();
            $("#formDetailPegawai").html(data);

            $("select.selectpicker").selectpicker();

            const padCanvas = document.querySelector("#sign-pad-pegawai");

            if (padCanvas) {
                signPad = new SignaturePad(padCanvas, {
                    dotSize: 0.5,
                });

                const dataURI = padCanvas.dataset.file;

                if (dataURI) {
                    signPad.fromDataURL(dataURI, {
                        ratio: 1,
                    });
                }
            }
        },
    });
}

function prosesPegawai() {
    let formPegawai = document.getElementById("formPegawai");
    let dataForm = new FormData(formPegawai);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_informasi/proses-pegawai.php",
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
