<?php
/**
 * @package chamilo.plugin.buy_courses
 */
/**
 * Initialization
 */

require_once '../../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once 'buy_course_plugin.class.php';
require_once 'buy_course.lib.php';

$course_plugin = 'buy_courses';
$plugin = Buy_CoursesPlugin::create();
$_cid = 0;
$teacher = api_is_platform_admin();
if ($teacher) {
    $interbreadcrumb[] = array("url" => "configuration.php", "name" => $plugin->get_lang('bc_setting_courses_available'));
    $interbreadcrumb[] = array("url" => "paymentsetup.php", "name" => $plugin->get_lang('bc_setting_pay'));
}
//echo var_dump($_SESSION);
//exit;
$tpl = new Template('Listado Cursos en venta');

//api_protect_course_script(true);

if (isset($_SESSION['bc_exito'])) {
    $tpl->assign('rmensaje', 'SI');
    if ($_SESSION['bc_exito'] == true) {
        $message = sprintf(utf8_encode($plugin->get_lang($_SESSION['bc_mensaje'])), $_SESSION['bc_url']);
        unset($_SESSION['bc_url']);
        $tpl->assign('estilo', 'confirmation-message');
    } else {
        $message = utf8_encode($plugin->get_lang($_SESSION['bc_mensaje']));
        $tpl->assign('estilo', 'warning-message');
    }
    $tpl->assign('mensaje', $message);
    unset($_SESSION['bc_exito']);
    unset($_SESSION['bc_mensaje']);

} else {
    $tpl->assign('rmensaje', 'NO');
}

$lista_cursos = listado_cursos_user();
$lista_categorias = listado_categorias();
$tipo_moneda = busca_moneda();

$tpl->assign('server', $_configuration['root_web']);
$tpl->assign('cursos', $lista_cursos);
$tpl->assign('categorias', $lista_categorias);
$tpl->assign('moneda', $tipo_moneda);

$listing_tpl = 'buy_courses/view/list.tpl';
$content = $tpl->fetch($listing_tpl);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
