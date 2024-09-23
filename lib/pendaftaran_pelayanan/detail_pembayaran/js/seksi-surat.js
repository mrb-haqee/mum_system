const editors_hasilPemeriksaan = {};

function seksiFormSurat() {
    const kodeAntrian = $("#kodeAntrian").val();
    const kodeRM = $("#kodeRM").val();
    const param = $("#param").val();

    $.ajax({
        url: "seksi_surat/form-surat.php",
        type: "post",
        data: {
            kodeAntrian: kodeAntrian,
            kodeRM: kodeRM,
            param: param,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailPembayaran").html(data);
            $(".loader-custom").hide();

            $("select.selectpicker").selectpicker();
           
            formDetailSurat();
        },
    });
}

function formDetailSurat() {
    const jenisSurat = $("#jenisSurat").val();

    const linkMap = new Map([
        ["Surat Sehat", "seksi_surat/form-surat-sehat.php"],
        ["Surat Sakit", "seksi_surat/form-surat-sakit.php"],
        ["Surat Rujukan", "seksi_surat/form-surat-rujukan.php"],
        ["Surat MCU", "seksi_surat/form-surat-mcu.php"],
        ["Surat Narkoba", "seksi_surat/form-surat-narkoba.php"],
        ["Surat Rapid Test Antigen", "seksi_surat/form-surat-antigen.php"],
        ["Surat Sehat Internship", "seksi_surat/form-surat-sehat-internship.php"],
        ["Surat Radiologi", "seksi_surat/form-surat-radiologi.php"],
        ["Surat Pengantar Lab", "seksi_surat/form-surat-lab.php"]
    ]);

    const link = linkMap.get(jenisSurat);

    $.ajax({
        url: link,
        type: "post",
        data: {
            jenisSurat: jenisSurat,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailSurat").html(data);
            $(".loader-custom").hide();
            $("select.selectpicker").selectpicker();
            $("textarea[data-editor=active]").each((index, element) => {
                ClassicEditor.create(document.querySelector("#" + element.id))
                    .then((editor) => {
                        editors_hasilPemeriksaan[element.id] = editor;
                    })
                    .catch((error) => {
                        console.error(error);
                    });
            });
        },
    });
}

function prosesSurat() {
    const formSurat = document.getElementById("formSurat");
    const dataForm = new FormData(formSurat);
    
    if(dataForm.get('jenisSurat') === 'Surat MCU' || dataForm.get('jenisSurat') === 'Surat Radiologi' || dataForm.get('jenisSurat') === 'Surat Rujukan' || dataForm.get('jenisSurat') === 'Surat Pengantar Lab'){
        const listName = [];
        for (const id of Object.keys(editors_hasilPemeriksaan)) {
            const $name = $("#" + id).attr("name");
            dataForm.append($name, editors_hasilPemeriksaan[id].getData());
    
            listName.push($name);
        }
    }

    const validasi = formValidation(dataForm,['hasilPemeriksaan']);

    if (validasi) {
        $.ajax({
            url: "seksi_surat/proses-surat.php",
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

                seksiFormSurat();
            },
        });
    }
}

function konfirmasiBatalSurat(id, token) {
    Swal.fire({
        title: "Apakah anda yakin ?",
        text: "Setelah dibatalkan, proses tidak dapat diulangi!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya!",
        cancelButtonText: "Tidak!",
    }).then(function (result) {
        if (result.value) {
            $.ajax({
                url: `seksi_surat/proses-surat.php`,
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    id: id,
                    flag: "delete",
                },
                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);
                    seksiFormSurat();
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}
