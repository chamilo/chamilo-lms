<?php
/**
 * Initialization
 */
require_once dirname(__FILE__) . '/buy_course.lib.php';
require_once '../../../main/inc/global.inc.php';
require_once 'buy_course_plugin.class.php';

$_cid = 0;
$interbreadcrumb[] = array("url" => "list.php", "name" => 'Listado de cursos a la venta');
$interbreadcrumb[] = array("url" => "paymentsetup.php", "name" => get_lang('Configuraci&oacute;n pagos'));

$tpl = new Template('Configuraci&oacute;n de cursos disponibles');

$teacher = api_is_platform_admin();
api_protect_course_script(true);

if ($teacher) {
    // SINCRONIZAR TABLA DE CURSOS CON TABLA DEL PLUGIN
    sincronizar();
    $visibilidad = array();
    $visibilidad[] = get_course_visibility_icon('0');
    $visibilidad[] = get_course_visibility_icon('1');
    $visibilidad[] = get_course_visibility_icon('2');
    $visibilidad[] = get_course_visibility_icon('3');

    $lista_cursos = listado_cursos();
    $ruta = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/message_confirmation.png';
    $ruta2 = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/save.png';
    $tipo_moneda = busca_moneda();

    $tpl->assign('server', $_configuration['root_web']);
    $tpl->assign('cursos', $lista_cursos);
    $tpl->assign('visibilidad', $visibilidad);
    $tpl->assign('ruta_imagen_ok', $ruta);
    $tpl->assign('ruta_imagen_save', $ruta2);
    $tpl->assign('moneda', $tipo_moneda);

    $listing_tpl = 'buy_courses/view/configuration.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
