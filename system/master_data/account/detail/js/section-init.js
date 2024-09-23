document.addEventListener("readystatechange", function (event) {
    if (document.readyState === "complete") {
        btnExaminationTab("btn-tab", "btn-danger", "btn-light-danger");

        seksiFormInformasi();
        // seksiFormSubAccount();
    }
});

function notifikasi(status, pesan) {
    if (status === true) {
        toastr.success(pesan);
    } else {
        toastr.error(pesan);
    }
}
