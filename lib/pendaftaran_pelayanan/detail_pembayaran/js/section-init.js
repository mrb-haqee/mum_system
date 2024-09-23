document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        btnExaminationTab("btn-seksi-informasi-tab", "btn-primary", "btn-light-primary");

        seksiFormPemeriksaan();
    }
});

function config(tipe) {
    const config = {
        escort: {
            dir: "seksi_escort",
            form: seksiFormEscort,
            link: {
                harga: "data-harga-escort.php",
                proses: "proses-escort.php",
            },
            priceElemID: "#hargaEscort",
            qtyElemID: "#qtyEscort",
            resultElemID: "#subTotalEscort",
            itemVal: $("#idEscort").val(),
        },
        tindakan: {
            dir: "seksi_tindakan",
            form: seksiFormTindakan,
            link: {
                harga: "data-harga-tindakan.php",
                proses: "proses-tindakan.php",
            },
            priceElemID: "#hargaTindakan",
            qtyElemID: "#qtyTindakan",
            resultElemID: "#subTotalTindakan",
            itemVal: $("#idTindakan").val(),
        },
        laboratorium: {
            dir: "seksi_laboratorium",
            form: seksiFormLaboratorium,
            link: {
                harga: "data-harga-laboratorium.php",
                proses: "proses-laboratorium.php",
            },
            priceElemID: "#hargaLaboratorium",
            qtyElemID: "#qtyLaboratorium",
            resultElemID: "#subTotalLaboratorium",
            itemVal: $("#idProsedurLaboratorium").val(),
        },
        obat: {
            dir: "seksi_obat",
            form: seksiFormObat,
            link: {
                harga: "data-harga-obat.php",
                proses: "proses-obat.php",
            },
            priceElemID: "#hargaObat",
            qtyElemID: "#qtyObat",
            resultElemID: "#subTotalObat",
            itemVal: $("#idObat").val(),
        },
        alkes: {
            dir: "seksi_alkes",
            form: seksiFormAlkes,
            link: {
                harga: "data-harga-alkes.php",
                proses: "proses-alkes.php",
            },
            priceElemID: "#hargaAlkes",
            qtyElemID: "#qtyAlkes",
            resultElemID: "#subTotalAlkes",
            itemVal: $("#idAlkes").val(),
        },
        paket_laboratorium: {
            dir: "seksi_paket_lab",
            form: seksiFormPaketLaboratorium,
            link: {
                harga: "data-harga-paket-laboratorium.php",
                proses: "proses-paket-laboratorium.php",
            },
            priceElemID: "#hargaPaketLaboratorium",
            qtyElemID: "#qtyPaketLaboratorium",
            resultElemID: "#subTotalPaketLaboratorium",
            itemVal: $("#idPaketLaboratorium").val(),
        },
    };

    return config[tipe];
}

function showHarga(tipe) {
    const statusBPJS = $("#statusBPJS").val() ?? "";
    const kodeAntrian = $("input[name=kodeAntrian]").val();

    if (config(tipe) === undefined) {
        console.error("Tipe Tidak Valid");
        return false;
    }

    const { dir, link, priceElemID, qtyElemID, resultElemID, itemVal } = config(tipe);

    $.ajax({
        url: `${dir}/${link.harga}`,
        type: "POST",
        data: {
            id: itemVal,
            statusBPJS,
            kodeAntrian,
        },

        beforeSend: function () {
            //$('.overlay').show();
        },

        success: function (data) {
            //console.log(data);
            const { harga } = JSON.parse(data);

            $(priceElemID).val(harga);
            showSubTotal(priceElemID, qtyElemID, resultElemID);
            //$('.overlay').hide();
        },
    });
}

function showSubTotal(harga, qty, subTotal) {
    const hargaObat = rupiahToNumber($(harga).val());
    const qtyObat = rupiahToNumber($(qty).val());

    const nilai = hargaObat * qtyObat;

    $(subTotal).val(numberToRupiah(nilai)).trigger("keyup");
}

function konfirmasiBatal(id, token, tipe) {
    if (config(tipe) === undefined) {
        console.error("Tipe Tidak Valid");
        return false;
    }

    const { dir, link, form } = config(tipe);

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
                url: `${dir}/${link.proses}`,
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
                    form();
                },
            });
        } else if (result.dismiss === "cancel") {
            Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
        }
    });
}

function notifikasi(status, pesan) {
    if (status === true) {
        toastr.success(pesan);
    } else {
        toastr.error(pesan);
    }
}
