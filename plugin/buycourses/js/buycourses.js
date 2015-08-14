/* For licensing terms, see /license.txt */
/**
 * JS library for the Chamilo buy-courses plugin
 * @package chamilo.plugin.buycourses
 */
$(document).ready(function () {
    $("input[name='price']").change(function () {
        $(this).parent().next().children().attr("style", "display:none");
        $(this).parent().next().children().next().attr("style", "display:''");
        $(this).parent().parent().addClass("fmod");
        $(this).parent().parent().children().each(function () {
            $(this).addClass("btop");
        });
    });

    $("input[name='price']").keyup(function () {
        $(this).parent().next().children().attr("style", "display:none");
        $(this).parent().next().children().next().attr("style", "display:''");
        $(this).parent().parent().addClass("fmod");
        $(this).parent().parent().children().each(function () {
            $(this).addClass("btop");
        });
    });

    $("input[name='visible']").change(function () {
        $(this).parent().next().next().children().attr("style", "display:none");
        $(this).parent().next().next().children().next().attr("style", "display:''");
        $(this).parent().parent().addClass("fmod");
        $(this).parent().parent().children().each(function () {
            $(this).addClass("btop");
        });
    });

    $(".save").click(function () {
        var currentRow = $(this).closest("tr");
        var courseOrSessionObject ={
            tab: "save_mod",
            visible: currentRow.find("[name='visible']").is(':checked') ? 1 : 0,
            price: currentRow.find("[name='price']").val()
        };

        var course_id = $(this).attr('id');
        var courseOrSession = ($(this).closest("td").attr('id')).indexOf("session") > -1 ? "session_id" : "course_id";

        courseOrSessionObject[courseOrSession] = course_id;

        $.post("function.php", courseOrSessionObject,
            function (data) {
                if (data.status == "false") {
                    alert("Database Error");
                } else {
                    courseOrSession = courseOrSession.replace("_id", "");
                    $("#" + courseOrSession + data.course_id).children().attr("style", "display:''");
                    $("#" + courseOrSession + data.course_id).children().next().attr("style", "display:none");
                    $("#" + courseOrSession + data.course_id).parent().removeClass("fmod")
                    $("#" + courseOrSession + data.course_id).parent().children().each(function () {
                        $(this).removeClass("btop");
                    });
                }
            }, "json");
    });

    $('#sync').click(function (e) {
        $.post("function.php", {tab: "sync"},
            function (data) {
                if (data.status == "false") {
                    alert(data.contenido);
                } else {
                    alert(data.contenido);
                    location.reload();
                }
            }, "json");
        e.preventDefault();
        e.stopPropagation();
    });

    $(".filter").click(function (e) {
        var target = "#"+($(this).closest(".row").children().last()).attr("id");
        var filterFields = $(this).siblings("input");
        var filterFieldsData = { tab: $(this).attr("id") };
        $.each(filterFields, function() {
            // Get only the first class
            var className = $(this).attr("class").split(" ")[0];
            filterFieldsData[className] = $(this).val();
        });
        $.post("function.php", filterFieldsData,
            function (data) {
                if (data.status == "false") {
                    alert(data.content);
                    $(target).html('');
                } else {
                    $(target).html(data.content);
                }
                $(document).ready(acciones_ajax);
            }, "json");
        e.preventDefault();
        e.stopPropagation();
    });

    $("#cancel_order").click(function (e) {
        $.post("function.php", {tab: "unset_variables"});
        window.location.replace("list.php");
    });

    $(".clear_order").click(function (e) {
        var vid = $(this).parent().attr("id");
        $.post("function.php", {tab: "clear_order", id: vid},
            function (data) {
                location.reload();
            }, "json");

        e.preventDefault();
        e.stopPropagation();
    });

    $(".confirm_order").click(function (e) {
        var vid = $(this).parent().attr("id");
        $.post("function.php", {tab: "confirm_order", id: vid},
            function (data) {
                location.reload();
            }, "json");

        e.preventDefault();
        e.stopPropagation();
    });

    $(".slt_tpv").change(function () {
        var vcod = $(this).val();
        $.post("function.php", {tab: "activar_tpv", cod: vcod});
    });
});

function acciones_ajax() {
    $('.ajax').on('click', function () {
        var url = this.href;
        var dialog = $("#dialog");
        if ($("#dialog").length == 0) {
            dialog = $('<div id="dialog" style="display:none"></div>').appendTo('body');
        }
        width_value = 580;
        height_value = 450;
        resizable_value = true;
        new_param = get_url_params(url, 'width');
        if (new_param) {
            width_value = new_param;
        }
        new_param = get_url_params(url, 'height')
        if (new_param) {
            height_value = new_param;
        }
        new_param = get_url_params(url, 'resizable');
        if (new_param) {
            resizable_value = new_param;
        }
        // load remote content
        dialog.load(
            url,
            {},
            function (responseText, textStatus, XMLHttpRequest) {
                dialog.dialog({
                    modal: true,
                    width: width_value,
                    height: height_value,
                    resizable: resizable_value
                });
            });
        //prevent the browser to follow the link
        return false;
    });


}

