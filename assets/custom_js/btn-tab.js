function btnExaminationTab(classAction, activeColor, defaultColor) {
    $(document).on("click", `button.${classAction}`, function (ev) {
        $(`button.${classAction}.${activeColor}`).removeClass(activeColor).addClass(defaultColor);

        if ($(this).hasClass(defaultColor)) $(this).removeClass(defaultColor).addClass(activeColor);
    });
}

function btnExaminationTab2(classAction, classColor1, classColorLight1, classColor2, classColorLight2) {
    let btnStatus, btnStatusCurr;

    let btnEl = $("." + classAction);

    btnEl.on("click", function (e) {
        e.preventDefault();

        btnStatusCurr = $(this).attr("data-status");

        btnEl.each(function () {
            btnStatus = $(this).attr("data-status");

            let btnHasClass1 = $(this).hasClass(classColor1);
            let btnHasClass2 = $(this).hasClass(classColor2);

            if (btnStatus == "ongoing") {
                if (btnHasClass1) {
                    $(this).addClass(classColorLight1);
                    $(this).removeClass(classColor1);
                }
            } else {
                if (btnHasClass2) {
                    $(this).addClass(classColorLight2);
                    $(this).removeClass(classColor2);
                }
            }
        });

        if (btnStatusCurr == "ongoing") {
            $(this).addClass(classColor1);
            $(this).removeClass(classColorLight1);
        } else {
            $(this).addClass(classColor2);
            $(this).removeClass(classColorLight2);
        }
    });
}
