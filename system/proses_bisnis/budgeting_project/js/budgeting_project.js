document.addEventListener("readystatechange", function (event) {
  if (document.readyState === "complete") {
    dataDaftarBudgetingProject();
  }
});

function cariBudgetingProject() {
  const kataKunciData = $("#kataKunciData").val();

  if (kataKunciData) {
    $.ajax({
      url: "data-daftar-budgeting-project.php",
      type: "post",
      data: {
        kataKunciData: kataKunciData,
        flag: "cari",
      },
      beforeSend: function () {
        $(".overlay").show();
      },
      success: function (data, status) {
        $("#dataDaftarBudgetingProject").html(data);
        $(".overlay").hide();
      },
    });
  }
}

function dataDaftarBudgetingProject() {
  $.ajax({
    url: "data-daftar-budgeting-project.php",
    type: "post",
    data: {
      flag: "daftar",
    },
    beforeSend: function () {
      $(".overlay").show();
    },
    success: function (data, status) {
      //console.log(data);
      $("#dataDaftarBudgetingProject").html(data);
      $(".overlay").hide();
    },
  });
}

function getDetailBudgeting(idBudgetingProject, IDcontainer) {
  $.ajax({
    url: "detail-budgeting.php",
    type: "post",
    data: {
      flagData: "daftar",
      idBudgetingProject,
    },
    success: function (data, status) {
      $(IDcontainer).html(data);
    },
  });
}

function konfirmasiBatalBudgetingProject(id, token) {
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
        url: "proses-budgeting-project.php",
        type: "post",
        data: {
          tokenCSRFForm: token,
          idBudgetingProject: id,
          flag: "delete",
        },
        dataType: "json",

        success: function (data) {
          const { status, pesan } = data;
          notifikasi(status, pesan);

          dataDaftarBudgetingProject();
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
