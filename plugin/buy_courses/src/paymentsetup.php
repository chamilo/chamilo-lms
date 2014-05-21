<?php
/**
 * Initialization
 */
require_once dirname(__FILE__) . '/buy_course.lib.php';
require_once '../../../main/inc/global.inc.php';
require_once 'lib/buy_course_plugin.class.php';

$_cid = 0;
$interbreadcrumb[] = array("url" => "list.php", "name" => 'Listado de cursos a la venta');
$interbreadcrumb[] = array("url" => "configuration.php", "name" => get_lang('Configuraci&oacute;n de cursos disponibles'));

$tpl = new Template('Configuraci&oacute;n de Pagos');

$teacher = api_is_platform_admin();
api_protect_course_script(true);

if ($teacher) {
    // SINCRONIZAR TABLA DE CURSOS CON TABLA DEL PLUGIN
    $lista_monedas = listado_monedas();

    $param_paypal = parametros_paypal();
    $param_transf = parametros_transf();

    $ruta = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/message_confirmation.png';
    $ruta2 = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/save.png';
    $ruta3 = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/more.png';
    $ruta4 = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/borrar.png';
    $ruta5 = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/acces_tool.gif';

    $plugin = Buy_CoursesPlugin::create();
    $paypal_enable = $plugin->get('paypal_enable');
    $tarjeta_enable = $plugin->get('tarjet_credit_enable');
    $transference_enable = $plugin->get('transference_enable');

    $tpl->assign('server', $_configuration['root_web']);
    $tpl->assign('monedas', $lista_monedas);
    $tpl->assign('paypal', $param_paypal);
    $tpl->assign('transferencia', $param_transf);
    $tpl->assign('ruta_imagen_ok', $ruta);
    $tpl->assign('ruta_imagen_save', $ruta2);
    $tpl->assign('ruta_more', $ruta3);
    $tpl->assign('ruta_borrar', $ruta4);
    $tpl->assign('ruta_ver', $ruta5);
    $tpl->assign('paypal_enable', $paypal_enable);
    $tpl->assign('tarjeta_enable', $tarjeta_enable);
    $tpl->assign('transference_enable', $transference_enable);


    $listing_tpl = 'buy_courses/view/paymentsetup.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
