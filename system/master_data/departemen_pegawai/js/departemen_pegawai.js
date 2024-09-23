document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        dataDaftarDepartemenPegawai();
    }
});

function dataCariDaftarDepartemenPegawai() {
    const kataKunciData = $("#kataKunciData").val();

    if (kataKunciData) {
        $.ajax({
            url: "datadaftardepartemenpegawai.php",
            type: "post",
            data: {
                kataKunciData: kataKunciData,
                flagData: "cari",
            },
            beforeSend: function () {
                $(".overlay").show();
            },
            success: function (data, status) {
                $("#dataDaftarDepartemenPegawai").html(data);
                $(".overlay").hide();
            },
        });
    }
}

function dataDaftarDepartemenPegawai() {
    $.ajax({
        url: "datadaftardepartemenpegawai.php",
        type: "post",
        data: {
            flagData: "daftar",
        },
        beforeSend: function () {
            $(".overlay").show();
        },
        success: function (data, status) {
            $("#dataDaftarDepartemenPegawai").html(data);
            $(".overlay").hide();
        },
    });
}

$(function () {
    $(document).on("click", "#tombolTambahDepartemenPegawai", function (e) {
        e.preventDefault();
        $("#modalFormDepartemenPegawai").modal("show");

        $.ajax({
            url: "formdepartemenpegawai.php",
            type: "post",
            data: {
                idDepartemenPegawai: "",
                flag: "",
            },
            beforeSend: function () {},
            success: function (data, status) {
                $("#dataFormDepartemenPegawai").html(data);
                $("select.selectpicker").selectpicker();
            },
        });
    });
});

$(function () {
    $(document).on("click", ".tombolEditDepartemenPegawai", function (e) {
        e.preventDefault();
        $("#modalFormDepartemenPegawai").modal("show");

        $.ajax({
            url: "formdepartemenpegawai.php",
            type: "post",
            data: {
                idDepartemenPegawai: $(this).attr("data-idDepartemenPegawai"),
                flag: "update",
            },
            beforeSend: function () {},
            success: function (data, status) {
                $("#dataFormDepartemenPegawai").html(data);
                $("select.selectpicker").selectpicker();
            },
        });
    });
});

function prosesDepartemenPegawai() {
    const formDepartemenPegawai = document.getElementById("formDepartemenPegawai");
    const dataForm = new FormData(formDepartemenPegawai);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "prosesdepartemenpegawai.php",
            type: "post",
            enctype: "multipart/form-data",
            processData: false,
            contentType: false,
            data: dataForm,

            beforeSend: function () {},

            success: function (data, status) {
                // console.log(data);
                let dataJSON = JSON.parse(data);
                notifikasi(dataJSON);
                dataDaftarDepartemenPegawai();
            },
        });
    }
}

function konfirmasiBatalDepartemenPegawai(id, token) {
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
                url: "prosesdepartemenpegawai.php",
                type: "post",
                data: {
                    tokenCSRFForm: token,
                    idDepartemenPegawai: id,
                    flag: "delete",
                },

                success: function (data, status) {
                    //console.log(data);
                    let dataJSON = JSON.parse(data);
                    notifikasi(dataJSON);
                    dataDaftarDepartemenPegawai();
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}
