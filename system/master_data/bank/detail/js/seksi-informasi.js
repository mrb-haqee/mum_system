$(function () {
    $(document).on("input", "input[type=file].dropify", function (e) {
        const name = $(this).attr("name");
        $(".btn-group[data-name=" + name + "] button:nth-child(1)").click();
    });
});

function seksiFormInformasi() {
    const kodeBank = $("#kodeBank").val();

    $.ajax({
        url: "seksi_informasi/form-informasi.php",
        type: "post",
        data: {
            kodeBank: kodeBank,
        },
        beforeSend: function () {
            $(".loader-custom").show();
        },
        success: function (data, status) {
            $("#formDetailBank").html(data);
            $(".loader-custom").hide();

            $("select.selectpicker").selectpicker();

            $(".dropify").dropify({
                messages: {
                    default: "Drag and drop a file here or click ( FILE < 1 MB [JPG,JPEG,PNG])",
                    replace: "Drag and drop or click to replace ( FILE < 1 MB [JPG,JPEG,PNG])",
                    remove: "Remove",
                    error: "Ooops, something wrong happended.",
                },
            });
            $(".dropify-clear").remove();
        },
    });
}

function prosesBank() {
    const formBank = document.getElementById("formBank");
    const dataForm = new FormData(formBank);

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

function prosesFile(btn, name, flag) {
    const tokenCSRFForm = $(`input[name=tokenCSRFForm]`).val();

    const element = $("input[name=" + name + "]");
    const elementHeight = element.data("height");

    const noForm = btn.data("no-form");
    const folder = btn.data("folder");
    const kode = btn.data("kode");
    const ajaxURL = atob(btn.data("proses")) + ".php";

    const dataForm = new FormData();

    dataForm.append("flag", flag);
    dataForm.append("tokenCSRFForm", tokenCSRFForm);
    dataForm.append("noForm", noForm);
    dataForm.append("kodeFile", kode);
    dataForm.append("folder", folder);

    if (flag === "uploadFile") {
        const file = element[0].files[0] === undefined ? "" : element[0].files[0];

        dataForm.append("htmlName", name);
        dataForm.append(name, file);
    } else if (flag == "deleteFile") {
        btn.removeData();
    }

    const validasi = formValidation(dataForm);
    $(".btn-group[data-name=" + name + "] button").attr("disabled", "disabled");
    // btn.attr("disabled", "disabled");

    if (validasi === true) {
        $.ajax({
            url: ajaxURL,
            type: "POST",
            enctype: "multipart/form-data",
            processData: false,
            contentType: false,
            data: dataForm,
            dataType: "json",
            beforeSend: function () {
                const progressBarElement = `<div class="col-md-12" id="boxProgressBar" data-progress="${name}">
                                                <div class="progress" style="height : 14px">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>`;

                $(progressBarElement).insertBefore("div[data-box=" + name + "]");
            },
            success: function (data) {
                const { status, pesan, more } = data;
                const { kodeFile, previewPath } = more;

                $("div[data-progress=" + name + "]").remove();

                if (flag === "uploadFile" && status === true) {
                    if ($(".btn-group[data-name=" + name + "] button:nth-child(1)").hasClass("btn-success")) {
                        $(".btn-group[data-name=" + name + "] button:nth-child(1)")
                            .removeClass("btn-success")
                            .addClass("btn-secondary");
                    }
                } else if (flag === "deleteFile" && status === true) {
                    if ($(".btn-group[data-name=" + name + "] button:nth-child(1)").hasClass("btn-secondary")) {
                        $(".btn-group[data-name=" + name + "] button:nth-child(1)")
                            .removeClass("btn-secondary")
                            .addClass("btn-success");
                    }
                }

                notifikasi(status, pesan);

                const boxElement = $("div[data-box=" + name + "]");
                let inputElement = "";

                if (status == true) {
                    inputElement = `<input type="file" class="dropify" name="${name}" data-default-file="${previewPath}" data-height="${elementHeight}" data-allowed-file-extensions="pdf png" data-max-file-size="1M" />`;
                } else {
                    inputElement = `<input type="file" class="dropify" name="${name}" data-default-file="" data-height="${elementHeight}" data-allowed-file-extensions="pdf png" data-max-file-size="1M"/>`;
                }

                boxElement.empty().html(inputElement);
                $(".btn-group[data-name=" + name + "] button[data-url]")
                    .removeAttr("data-url")
                    .attr("data-url", previewPath);

                $(".dropify").dropify({
                    messages: {
                        default: "Drag and drop a file here or click ( FILE < 1 MB [JPG,JPEG,PNG])",
                        replace: "Drag and drop or click to replace ( FILE < 1 MB [JPG,JPEG,PNG])",
                        remove: "Remove",
                        error: "Ooops, something wrong happended.",
                    },
                });
                $(".dropify-clear").remove();

                $(".btn-group[data-name=" + name + "] button").removeAttr("disabled");
            },
        });
    } else {
        notifikasi(false, "Proses Gagal, Form Belum Terisi Dengan Lengkap");
        $(".btn-group[data-name=" + name + "] button").removeAttr("disabled");
    }
}
