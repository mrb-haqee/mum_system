function seksiFormPenghasilan(idPenghasilanPegawai='') {
    let kodePegawai = $("#kodePegawai").val();
    $.ajax({
        url: "seksi_penghasilan/form-penghasilan.php",
        type: "post",
        data: {
            kodePegawai: kodePegawai,
            idPenghasilanPegawai
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            //console.log(data);
            $("#formDetailPegawai").html(data);
            $(".loader-custom").hide();
            $("select.selectpicker").selectpicker();
        },
    });
}

function prosesPenghasilan() {
    let formPenghasilan = document.getElementById("formPenghasilan" );
    let dataForm = new FormData(formPenghasilan);


    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "seksi_penghasilan/proses-penghasilan.php",
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

                seksiFormPenghasilan();
            },
        });
    }
}

function konfirmasiBatalPenghasilan(id, token) {
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
                url: "seksi_penghasilan/proses-penghasilan.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idPenghasilanPegawai: id,
                    flag: "delete",
                },

                dataType: "json",

                success: function (data) {
                    const { status, pesan } = data;
                    notifikasi(status, pesan);

                    seksiFormPenghasilan();
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}
