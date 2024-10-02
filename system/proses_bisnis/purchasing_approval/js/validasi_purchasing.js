document.addEventListener("readystatechange", function (event) {
  if (document.readyState === "complete") {
    btnExaminationTab(
      "btn-detail-purchasing-tab",
      "btn-danger",
      "btn-light-danger"
    );
    dataDaftarSR();
  }
  $("#periode").daterangepicker({
    buttonClasses: " btn",
    applyClass: "btn-primary",
    cancelClass: "btn-secondary",
    locale: {
      format: "YYYY-MM-DD",
    },
  });
});

function dataDaftarSR(
  statusPersetujuan = "",
  resetDate = true
) {
  if (!statusPersetujuan) {
    statusPersetujuan = $(".btn-detail-purchasing-tab.btn-danger").data(
      "in-tab"
    );
  }

  let startDate = moment().format("YYYY-MM-1");
  let lastDate = moment().endOf("month").format("YYYY-MM-DD");

  let periode = $("#periode").val();

  if (resetDate && periode !== `${startDate} - ${lastDate}`) {
    $("#periode").removeAttr("onchange");

    let $datePicker = $("#periode").data("daterangepicker");
    console.log($datePicker);

    $("#periode").val(startDate + " - " + lastDate);

    $datePicker.setStartDate(startDate);
    $datePicker.setEndDate(lastDate);
    periode = `${startDate} - ${lastDate}`;
  }

  $.ajax({
    url: "daftar-daftar-validasi-purchasing.php",
    type: "post",
    data: {
      flag: "daftar",
      periode,
      statusPersetujuan,
    },
    beforeSend: function () {
      $(".overlay").show();
    },
    success: function (data, status) {
      $("#dataDaftarSR").html(data);
      $("#periode").attr("onchange", "dataDaftarSR('', false)");

      $(".overlay").hide();
    },
  });
}

function getDetailPurchasing(kodePO) {
  $("#modalDetailPurchasing").modal("show");
  $.ajax({
    url: "form-validasi-purchasing.php",
    type: "post",
    data: {
      kodePO,
    },
    success: function (data, status) {
      $("#dataDetailPurchasing").html(data);
      $("select.selectpicker").selectpicker();
    },
  });
}

function prosesValidasiPurchasing(kodePO, statusPersetujuan, token) {
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
          dataDaftarSR(inTab);
          $(".overlay").hide();

          notifikasi(data.status, data.pesan);
        },
      });
    } else if (result.dismiss === "cancel") {
      Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
    }
  });
}

// function konfirmasiBatalBudgetingProject(id, token) {
//   Swal.fire({
//     title: "Apakah anda yakin?",
//     text: "Setelah dibatalkan, proses tidak dapat diulangi!",
//     icon: "warning",
//     showCancelButton: true,
//     confirmButtonText: "Ya!",
//     cancelButtonText: "Tidak!",
//   }).then(function (result) {
//     if (result.value) {
//       $.ajax({
//         url: "proses-budgeting-project.php",
//         type: "post",
//         data: {
//           tokenCSRFForm: token,
//           idBudgetingProject: id,
//           flag: "delete",
//         },
//         dataType: "json",

//         success: function (data) {
//           const { status, pesan } = data;
//           notifikasi(status, pesan);

//           dataDaftarBudgetingProject();
//         },
//       });
//     } else if (result.dismiss === "cancel") {
//       Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
//     }
//   });
// }

function notifikasi(status, pesan) {
  if (status === true) {
    toastr.success(pesan);
  } else {
    toastr.error(pesan);
  }
}
