document.addEventListener("readystatechange", function (event) {
  if (document.readyState === "complete") {
    btnExaminationTab("btn-status-type-tab", "btn-danger", "btn-light-danger");
    dataDaftar();
    $("#periode").daterangepicker({
      buttonClasses: " btn",
      applyClass: "btn-primary",
      cancelClass: "btn-secondary",
      locale: {
        format: "YYYY-MM-DD",
      },
    });
  }
});

let isProcessing = false;

function dataDaftar(statusPO = "", dataDaftar = "", resetDate = false) {
  if (isProcessing) return;
  isProcessing = true;

  if (!statusPO) {
    statusPO = $(".btn-status-type-tab.btn-danger").data("status");
  }
  if (!dataDaftar) {
    dataDaftar = $(".btn-status-type-tab.btn-danger").data("daftar");
  }

  if (resetDate) {
    $("#periode").removeAttr("onchange");

    let startDate = moment().format("YYYY-MM-1");
    let lastDate = moment().endOf("month").format("YYYY-MM-DD");
    let $datePicker = $("#periode").data("daterangepicker");
    $("#periode").val(startDate + " - " + lastDate);

    $datePicker.setStartDate(startDate);
    $datePicker.setEndDate(lastDate);
  }

  // console.log(statusPO, dataDaftar);

  const periode = $("#periode").val();

  $.ajax({
    url: `daftar-daftar-${dataDaftar}.php`,
    type: "post",
    data: {
      flag: "daftar",
      periode,
      statusPO,
    },
    beforeSend: function () {
      $(".overlay").show();
    },
    success: function (data, status) {
      $("#boxDataDaftar").html(data);
      $("#periode").attr("onchange", "dataDaftarPengiriman('', false)");

      $(".overlay").hide();
    },
    complete: function () {
      isProcessing = false;
    },
  });
}

function getDetailPurchasing(kodePengiriman) {
  $("#modalDetailPurchasing").modal("show");
  $.ajax({
    url: "form-service-recipt.php",
    type: "post",
    data: {
      kodePengiriman,
    },
    success: function (data, status) {
      $("#dataDetailPurchasing").html(data);
      $("select.selectpicker").selectpicker();
    },
  });
}

function showSubTotal() {
  const hargaSatuan = rupiahToNumber($("#hargaSatuan").val());
  const qty = $("#qty").val();
  const subTotal = qty * hargaSatuan;

  $("#subTotal").val(numberToRupiah(subTotal));
}

function prosesSR(kodePO, statusPersetujuan, token) {
  const keterangan = $("#keterangan").val() ? $("#keterangan").val() : "";

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
        url: "proses-validasi-purchasing.php",
        type: "post",
        data: {
          flag: "update",
          kodePO,
          statusPersetujuan,
          keterangan,
          tokenCSRFForm: token,
        },
        dataType: "json",
        beforeSend: function () {
          $(".overlay").show();
        },
        success: function (data) {
          $("#modalDetailPurchasing").modal("hide");
          const inTab = $(".btn-detail-purchasing-tab.btn-danger").data(
            "in-tab"
          );
          dataDaftarPengiriman(inTab);
          $(".overlay").hide();

          notifikasi(data.status, data.pesan);
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
