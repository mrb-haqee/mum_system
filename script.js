document.addEventListener("keypress", function (event) {
    if (event.keyCode === 13 || event.which === 13) {
        prosesLogin();
    }
});

$(function () {
    $("select.selectpicker").selectpicker();
    $("[data-toggle=tooltip]").tooltip();
});

function prosesLogin() {
    const formLogin = document.getElementById("formLogin");
    const dataForm = new FormData(formLogin);

    const validasi = formValidation(dataForm);

    if (validasi) {
        $.ajax({
            url: "library/proseslogin.php",
            type: "post",
            enctype: "multipart/form-data",
            processData: false,
            contentType: false,
            data: dataForm,
            dataType: "json",

            beforeSend: function () {
                $(".overlay").show();
            },

            success: function (data) {
                $(".overlay").hide();

                const { status, pesan } = data;

                notifikasi(status, pesan);

                if (status) {
                    const { redirect } = data;
                    window.location.href = redirect;
                } else {
                    const { attempt } = data;
                    let text = "";

                    if (attempt > 0) {
                        text = `Kesempatan Percobaan Login Tersisa ${attempt} kali`;
                    } else {
                        text = "Kesempatan Percobaan Login Telah Habis";
                    }

                    $("#loginBtn").attr("title", text);
                    $("#loginBtn").tooltip();

                    $("#loginBtn").tooltip("show");
                }
            },
        });
    }
}

function notifikasi(status, pesan) {
    if (status === true) {
        toastr.success(pesan);
    } else {
        toastr.error(pesan);
    }
}
