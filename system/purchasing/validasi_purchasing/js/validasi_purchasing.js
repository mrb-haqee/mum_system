document.addEventListener("readystatechange", function (event) {
  if (document.readyState === "complete") {
    btnExaminationTab(
      "btn-detail-purchasing-tab",
      "btn-danger",
      "btn-light-danger"
    );
    dataDaftarValidasiPurchasing("Pending");
  }
});

function dataDaftarValidasiPurchasing(statusPersetujuan) {
  $.ajax({
    url: "daftar-daftar-validasi-purchasing.php",
    type: "post",
    data: {
      flag: "daftar",
      statusPersetujuan,
    },
    beforeSend: function () {
      $(".overlay").show();
    },
    success: function (data, status) {
      //console.log(data);
      $("#dataDaftarValidasiPurchasing").html(data);
      $(".overlay").hide();
    },
  });
}

function getDetailPurchasing(kodePurchasing) {
  $("#modalDetailPurchasing").modal("show");
  $.ajax({
    url: "form-validasi-purchasing.php",
    type: "post",
    data: {
      kodePurchasing,
    },
    success: function (data, status) {
      $("#dataDetailPurchasing").html(data);
      $("select.selectpicker").selectpicker();
    },
  });
}

function EditBtn() {
  $(".btn-detail-purchasing-tab").each(function () {
    if ($(this).hasClass("btn-light-danger")) {
      $(this).removeClass("btn-light-danger").addClass("btn-danger");
    } else if ($(this).hasClass("btn-danger")) {
      $(this).removeClass("btn-danger").addClass("btn-light-danger");
    }
  });
}

function prosesValidasiPurchasing(kodePurchasing, statusPersetujuan, token) {
  const keterangan = $("#keterangan").val();

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
          kodePurchasing,
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
          dataDaftarValidasiPurchasing(inTab);
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
