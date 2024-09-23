document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        btnExaminationTab("btn-seksi-informasi-tab", "btn-danger", "btn-light-danger");
        $("button.btn-seksi-informasi-tab.btn-danger").click();
    }
});

function notifikasi(status, pesan) {
    if (status === true) {
        toastr.success(pesan);
    } else {
        toastr.error(pesan);
    }
}
