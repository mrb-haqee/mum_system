document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        btnExaminationTab("btn-seksi-informasi-tab", "btn-primary", "btn-light-primary");

        seksiFormInformasi();
    }
});

function notifikasi(status, pesan) {
    if (status === true) {
        toastr.success(pesan);
    } else {
        toastr.error(pesan);
    }
}
