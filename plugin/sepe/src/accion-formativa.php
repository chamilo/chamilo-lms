<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a form for registering new users.
 *    @package     chamilo.auth
 */

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
    $cod_action = obtener_cod_action($_GET['cid']);
    $info = accion_formativa($cod_action);
    if ($info === false) {
        header("Location: listado-acciones-formativas.php");    
    }
    $templateName = $plugin->get_lang('accion_formativa');
    $interbreadcrumb[] = array("url" => "/plugin/sepe/src/menu_sepe_administracion.php", "name" => $plugin->get_lang('menu_sepe'));
    $interbreadcrumb[] = array("url" => "listado-acciones-formativas.php", "name" => $plugin->get_lang('listado_acciones_formativas'));
    $tpl = new Template($templateName);
    
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);    
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);    
        unset($_SESSION['sepe_message_error']);
    }
    
    $tpl->assign('info', $info);
    $tpl->assign('fecha_start', date("d/m/Y",strtotime($info['FECHA_INICIO'])));
    $tpl->assign('fecha_end', date("d/m/Y",strtotime($info['FECHA_FIN'])));
    $tpl->assign('cod_action', $cod_action);
    $listSpecialty = listSpecialty($cod_action);
    $tpl->assign('listSpecialty', $listSpecialty);
    $listParticipant = listParticipant($cod_action);
    $tpl->assign('listParticipant', $listParticipant);
    
    
    $listing_tpl = 'sepe/view/accion_formativa.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();

} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}
