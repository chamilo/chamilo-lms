<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a administrator menu.
 */
require_once '../config.php';

$plugin = SepePlugin::create();
$enable = $plugin->get('sepe_enable') == 'true';

$title = $plugin->get_lang('AdministratorSepe');
$pluginPath = api_get_path(WEB_PLUGIN_PATH).'sepe/src/';

if (api_is_platform_admin() && $enable) {
    $htmlText = '';
    $htmlText .= '<div class="panel panel-default">';
    $htmlText .= '<div class="panel-heading" role="tab">';
    $htmlText .= '<h4 class="panel-title">'.$title.'</h4>';
    $htmlText .= '</div>';
    $htmlText .= '<div class="panel-collapse collapse in" role="tabpanel">';
    $htmlText .= '<div class="panel-body">';
    $htmlText .= '<ul class="nav nav-pills nav-stacked">';
    $htmlText .= '<li>';
    $htmlText .= '<a href="'.$pluginPath.'identification-data.php">';
    $htmlText .= '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/list.png">';
    $htmlText .= $plugin->get_lang('DataCenter');
    $htmlText .= '</a>';
    $htmlText .= '</li>';
    $htmlText .= '<li>';
    $htmlText .= '<a href="'.$pluginPath.'formative-actions-list.php">';
    $htmlText .= '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/forms.png">';
    $htmlText .= $plugin->get_lang('FormativeActionsForm');
    $htmlText .= '</a>';
    $htmlText .= '</li>';
    $htmlText .= '<li>';
    $htmlText .= '<a href="'.$pluginPath.'configuration.php">';
    $htmlText .= '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/settings.png">';
    $htmlText .= $plugin->get_lang('Setting');
    $htmlText .= '</a>';
    $htmlText .= '</li>';
    $htmlText .= '</ul>';
    $htmlText .= '</div>';
    $htmlText .= '</div>';
    $htmlText .= '</div>';

    $templateName = $plugin->get_lang('MenuSepeAdministrator');
    $interbreadcrumb[] = ["url" => "/main/admin/index.php", "name" => get_lang('Administration')];
    $tpl = new Template($templateName);
    $tpl->assign('html_text', $htmlText);

    $listing_tpl = 'sepe/view/sepe-administration-menu.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
