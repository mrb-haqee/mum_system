function seksiFormInformasi() {
  const kodePengiriman = $("#kodePengiriman").val();
  const kodeSR = $("#kodeSR").val();

  $.ajax({
    url: "seksi_informasi/form-informasi.php",
    type: "post",
    data: {
      kodePengiriman,
      kodeSR,
    },
    beforeSend: function () {
      $(".loader-custom").show();
    },
    success: function (data, status) {
      $("#formDetail").html(data);
      $(".loader-custom").hide();

      $("select.selectpicker").selectpicker();
    },
  });
}

function showSubTotal(index) {
  const hargaSatuan = rupiahToNumber($("#hargaSatuan" + index).val());
  const qty = rupiahToNumber($("#qty" + index).val());
  const subTotal = qty * hargaSatuan;
  $("#subTotal" + index).val(numberToRupiah(subTotal));
}

function prosesUpdateSRDetail(index) {
  const tokenCSRFForm = $(`input[name=tokenCSRFForm]`).val();
  const idSRDetail = $("#idSRDetail" + index).val();
  const qty = $("#qty" + index).val();
  const hargaSatuan = $("#hargaSatuan" + index).val();
  const subTotal = $("#subTotal" + index).val();

  const dataForm = new FormData();

  dataForm.append("flag", 'updateDetail');
  dataForm.append("tokenCSRFForm", tokenCSRFForm);
  dataForm.append("idSRDetail", idSRDetail);
  dataForm.append("qty", qty);
  dataForm.append("hargaSatuan", hargaSatuan);
  dataForm.append("subTotal", subTotal);

  $.ajax({
    url: "seksi_informasi/proses-informasi.php",
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

function prosesSR() {
  const formSR = document.getElementById("formSR");
  const dataForm = new FormData(formSR);

  const validasi = formValidation(dataForm);

  if (validasi) {
    $.ajax({
      url: "seksi_informasi/proses-informasi.php",
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
