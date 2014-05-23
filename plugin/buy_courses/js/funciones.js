$(document).ready(function () {
    $("input[name='price']").change(function () {
        $(this).parent().next().children().attr("style", "display:none");
        $(this).parent().next().children().next().attr("style", "display:''");
        $(this).parent().parent().addClass("fmod")
        $(this).parent().parent().children().each(function () {
            $(this).addClass("btop");
        });
    });

    $("input[name='price']").keyup(function () {
        $(this).parent().next().children().attr("style", "display:none");
        $(this).parent().next().children().next().attr("style", "display:''");
        $(this).parent().parent().addClass("fmod")
        $(this).parent().parent().children().each(function () {
            $(this).addClass("btop");
        });
    });

    $("input[name='visible']").change(function () {
        $(this).parent().next().next().children().attr("style", "display:none");
        $(this).parent().next().next().children().next().attr("style", "display:''");
        $(this).parent().parent().addClass("fmod")
        $(this).parent().parent().children().each(function () {
            $(this).addClass("btop");
        });
    });

    $(".guardar").click(function () {
        var vvisible = $(this).parent().parent().prev().prev().children().attr("checked");
        var vprice = $(this).parent().parent().prev().children().attr("value");
        var idcurso = $(this).parent().parent().attr("id");
        $.post("function.php", {tab: "save_mod", id: idcurso, visible: vvisible, price: vprice},
            function (data) {
                if (data.status == "false") {
                    alert("Error database");
                } else {
                    $("#curso" + data.id).children().attr("style", "display:''");
                    $("#curso" + data.id).children().next().attr("style", "display:none");
                    $("#curso" + data.id).parent().removeClass("fmod")
                    $("#curso" + data.id).parent().children().each(function () {
                        $(this).removeClass("btop");
                    });
                }
            }, "json");

    });

    $('#sincronizar').click(function (e) {
        $.post("function.php", {tab: "sincronizar"},
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


    $('#confirmar_filtro').click(function (e) {
        var vcurso = $("#course_name").attr("value");
        var pmin = $("#price_min").attr("value");
        var pmax = $("#price_max").attr("value");
        if ($("#mostrar_disponibles").attr("checked") == "checked") {
            var vmostrar = "SI";
        } else {
            var vmostrar = "NO";
        }
        var vcategoria = $("#categoria_cursos").attr("value");
        $.post("function.php", {tab: "filtro_cursos", curso: vcurso, pricemin: pmin, pricemax: pmax, mostrar: vmostrar, categoria: vcategoria},
            function (data) {
                if (data.status == "false") {
                    alert(data.contenido);
                    $("#resultado_cursos").html('');
                } else {
                    $("#resultado_cursos").html(data.contenido);
                }
                $(document).ready(acciones_ajax);
            }, "json");

        e.preventDefault();
        e.stopPropagation();
    });

    $("#save_money").click(function (e) {
        var tipo_moneda = $("#tipo_moneda").attr("value");
        $.post("function.php", {tab: "guardar_moneda", moneda: tipo_moneda},
            function (data) {
                alert(data.contenido);
            }, "json");

        e.preventDefault();
        e.stopPropagation();
    });

    $("#save_paypal").click(function (e) {
        var name = $("#username").attr("value");
        var clave = $("#password").attr("value");
        var firma = $("#signature").attr("value");
        if ($("#sandbox").attr("checked") == "checked") {
            var vsandbox = "SI";
        } else {
            var vsandbox = "NO";
        }
        $.post("function.php", {tab: "guardar_paypal", username: name, password: clave, signature: firma, sandbox: vsandbox},
            function (data) {
                alert(data.contenido);
            }, "json");

        e.preventDefault();
        e.stopPropagation();
    });

    $("#add_account").click(function (e) {
        var tname = $("#tname").attr("value");
        var taccount = $("#taccount").attr("value");
        var tswift = $("#tswift").attr("value");
        if (tname == '' || taccount == '') {
            alert("Complete los campos antes de insertar");
        } else {
            $.post("function.php", {tab: "add_account", name: tname, account: taccount, swift: tswift},
                function (data) {
                    location.reload();
                }, "json");
        }
        e.preventDefault();
        e.stopPropagation();
    });

    $(".delete_account").click(function (e) {
        var vid = $(this).parent().attr("id");
        $.post("function.php", {tab: "delete_account", id: vid},
            function (data) {
                location.reload();
            }, "json");

        e.preventDefault();
        e.stopPropagation();
    });

    $("#cancelapedido").click(function (e) {
        $.post("function.php", {tab: "borrar_variables"});
        window.location.replace("list.php");
    });

    $(".borrar_pedido").click(function (e) {
        var vid = $(this).parent().attr("id");
        $.post("function.php", {tab: "borrar_pedido", id: vid},
            function (data) {
                location.reload();
            }, "json");

        e.preventDefault();
        e.stopPropagation();
    });

    $(".confirmar_pedido").click(function (e) {
        var vid = $(this).parent().attr("id");
        $.post("function.php", {tab: "confirmar_pedido", id: vid},
            function (data) {
                location.reload();
            }, "json");

        e.preventDefault();
        e.stopPropagation();
    });

    $(".setting_tpv").click(function () {
        var vcod = $(this).attr("id");
        $.post("function.php", {tab: "cargar_tpv_configuracion", cod: vcod},
            function (data) {
                $("#resultado_tpv").html(data.contenido);
                $("#guardar_datos_tpv").click(function (e) {
                    var vcod = $("#conf_tpv").attr("value");
                    var num = $("#num_parametros").attr("value");
                    var vaction = $("#action").attr("value");
                    var array = [];
                    for (var i = 0; i < num; i++) {
                        var selector = '#valor_tpv' + i;
                        array.push($(selector).attr("value"));
                    }
                    $.post("function.php", {tab: "save_tpv", cod: vcod, nump: num, action: vaction, parametros: array},
                        function (data) {
                            alert(data.contenido);
                            $("#resultado_tpv").html("");
                        }, "json");

                    e.preventDefault();
                    e.stopPropagation();
                });
            }, "json");
    });

    $(".slt_tpv").change(function () {
        var vcod = $(this).attr("value");
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
		