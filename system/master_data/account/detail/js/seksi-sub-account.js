$(function () {
  $(document).on("input", "input[type=file].dropify", function (e) {
    const name = $(this).attr("name");
    $(".btn-group[data-name=" + name + "] button:nth-child(1)").click();
  });
});

function seksiFormSubAccount(idSubAccount = "") {
  const kodeAccount = $("#kodeAccount").val();

  $.ajax({
    url: "seksi_sub_account/form-sub-account.php",
    type: "post",
    data: {
      kodeAccount: kodeAccount,
      idSubAccount: idSubAccount,
    },
    beforeSend: function () {
      $(".loader-custom").show();
    },
    success: function (data, status) {
      $("#formDetailAccount").html(data);
      $(".loader-custom").hide();

      $("select.selectpicker").selectpicker();
    },
  });
}

function prosesSubAccount() {
  // let data = $("#formSubAccount").serializeArray()
  // console.log(data);

  const formSubAccount = document.getElementById("formSubAccount");
  const dataForm = new FormData(formSubAccount);

  const validasi = formValidation(dataForm);

  if (validasi) {
    $.ajax({
      url: "seksi_sub_account/proses-sub-account.php",
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

        seksiFormSubAccount();
      },
    });
  }
}

function konfirmasiHapusSubAccount(id, token) {
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
        url: "seksi_sub_account/proses-sub-account.php",
        type: "post",
        data: {
          tokenCSRFForm: token,
          idSubAccount: id,
          flag: "delete",
        },
        dataType: "json",

        success: function (data) {
          const { status, pesan } = data;
          notifikasi(status, pesan);

          seksiFormSubAccount();
        },
      });
    } else if (result.dismiss === "cancel") {
      Swal.fire("Dibatalkan", "Proses dibatalkan!", "error");
    }
  });
}
