<?php
/* For licensing terms, see /license.txt */

use \ChamiloSession as Session;

require_once '../config.php';
/*
require_once dirname(__FILE__).'/sepe.lib.php';
require_once '../../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once '../lib/sepe_plugin.class.php';
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';
*/
$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if (api_is_platform_admin()) {
    $templateName = $plugin->get_lang('listado_acciones_formativas');
    $interbreadcrumb[] = array("url" => "/plugin/sepe/src/menu_sepe_administracion.php", "name" => $plugin->get_lang('menu_sepe'));
    $tpl = new Template($templateName);
    
    if (isset($_SESSION['sepe_message_info'])){
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);    
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])){
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);    
        unset($_SESSION['sepe_message_error']);
    }

    $lista_curso_acciones = listCourseAction();
    /*
    echo "<pre>";
    echo var_dump($lista_curso_acciones);
    echo "</pre>";
    exit;
    */
    $lista_curso_libre_acciones = listCourseFree();
    $lista_acciones_libres = listActionFree();
    
    $tpl->assign('lista_curso_acciones', $lista_curso_acciones);
    $tpl->assign('lista_curso_libre_acciones', $lista_curso_libre_acciones);
    $tpl->assign('lista_acciones_libres', $lista_acciones_libres);
    
    $listing_tpl = 'sepe/view/listado_acciones_formativas.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();

} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}
