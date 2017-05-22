<?php
/* For licensing terms, see /license.txt */
use \ChamiloSession as Session;

require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

$is_enable = $plugin->get('sepe_enable');
$title="Administraci&oacute;n SEPE";
$pluginPath = api_get_path(WEB_PLUGIN_PATH).'sepe/src/';

if (api_is_platform_admin()) {
    $html_text = '';
    $html_text .= '<div class="panel panel-default">';
    $html_text .= '<div class="panel-heading" role="tab">';
    $html_text .= '<h4 class="panel-title">'.$title.'</h4>';
    $html_text .= '</div>';
    $html_text .= '<div class="panel-collapse collapse in" role="tabpanel">';
    $html_text .= '<div class="panel-body">';
    $html_text .= '<ul class="nav nav-pills nav-stacked">';
    $html_text .= '<li>';
    $html_text .= '<a href="'.$pluginPath.'datos-identificativos.php">';
        $html_text .= '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/list.png">';
        $html_text .=$plugin->get_lang('datos_centro');
    $html_text .= '</a>';
    $html_text .= '</li>';
    $html_text .= '<li>';
    $html_text .= '<a href="'.$pluginPath.'listado-acciones-formativas.php">';
        $html_text .= '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/forms.png">';
        $html_text .=$plugin->get_lang('formulario_acciones_formativas');
    $html_text .= '</a>';
    $html_text .= '</li>';
    $html_text .= '<li>';
    $html_text .= '<a href="'.$pluginPath.'configuracion.php">';
        $html_text .= '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/settings.png">';
        $html_text .=$plugin->get_lang('configuracion_sepe');
    $html_text .= '</a>';
    $html_text .= '</li>';
    $html_text .= '</ul>';
    $html_text .= '</div>';
    $html_text .= '</div>';
    $html_text .= '</div>';
    
    $templateName = $plugin->get_lang('menu_sepe_administracion');
    $interbreadcrumb[] = array("url" => "/main/admin/index.php", "name" => get_lang('Administration'));
    $tpl = new Template($templateName);
    $tpl->assign('html_text', $html_text);
        
    $listing_tpl = 'sepe/view/menu_sepe_administracion.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
    
} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}
