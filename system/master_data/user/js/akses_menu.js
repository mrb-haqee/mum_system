$(function () {
    $(document).on("click", ".row-input", function (e) {
        const idSubMenu = $(this).data("id");

        const checkbox = $('input[type=checkbox][data-switch=true][data-id="' + idSubMenu + '"]');
        checkbox.bootstrapSwitch("toggleState");
    });
    $(document).on("click", ".bootstrap-switch-label", function (e) {
        e.stopPropagation();
    });
});

function daftarAksesMenu(idUserAccount) {
    $("#modalAksesMenu").modal("show");
    $.ajax({
        url: "akses_menu/daftaraksesmenu.php",
        type: "post",
        data: {
            idUserAccount,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#boxDaftarAksesMenu").html(data);
            $("input[type=checkbox][data-switch=true]").bootstrapSwitch({
                onSwitchChange: function (event, state) {
                    const checkbox = $(event.target);

                    const idItem = checkbox.data("id");
                    const idUserAccount = checkbox.data("user");
                    const type = checkbox.data("type");

                    const status = state ? "Active" : "Non Active";

                    prosesAksesMenu(type, idUserAccount, idItem, status);
                },
            });
        },
    });
}

function getListAktivasiHak(idUserAccount, idSubMenu) {
    $("#modalAksesMenu").modal("show");
    $.ajax({
        url: "akses_menu/listaktivasihak.php",
        type: "post",
        data: {
            idUserAccount,
            idSubMenu,
        },
        beforeSend: function () {},
        success: function (data, status) {
            $("#boxTipe_" + idSubMenu).html(data);
        },
    });
}

function prosesAksesMenu(type, idUserAccount, idItem, statusMenu) {
    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();
    $.ajax({
        url: "akses_menu/prosesaksesmenu.php",
        type: "post",
        data: {
            type,
            flag: "aksesMenu",
            idUserAccount,
            idItem,
            statusMenu: statusMenu,
            tokenCSRFForm,
        },
        dataType: "json",
        success: function (data) {
            const { status, pesan } = data;
            notifikasi(status, pesan);

            if (type === "menu") {
                getListAktivasiHak(idUserAccount, idItem);
            }
        },
    });
}

function prosesAktivasiHak(btn, idUserAccount, idSubMenu, id, tipeAkses) {
    event.stopPropagation();
    const statusHak = btn.data("status");
    const tokenCSRFForm = $("input[name=tokenCSRFForm]").val();

    btn.attr("disabled", "disabled");

    $.ajax({
        url: "akses_menu/prosesaksesmenu.php",
        type: "post",
        data: {
            flag: "aktivasiHak",
            idUserAccount,
            idUserDetail: id,
            tipeAkses,
            statusHak,
            tokenCSRFForm,
        },
        dataType: "json",
        success: function (data) {
            const { status, pesan } = data;
            notifikasi(status, pesan);

            getListAktivasiHak(idUserAccount, idSubMenu);
        },
    });
}
