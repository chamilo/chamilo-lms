<?php
/* For license terms, see /license.txt */

/**
 * Index of the Sepe plugin.
 */
$plugin = SepePlugin::create();
$enable = $plugin->get('sepe_enable') == 'true';
$title = $plugin->get_lang('AdministratorSepe');
$pluginPath = api_get_path(WEB_PLUGIN_PATH).'sepe/src/';
if (api_is_platform_admin() && $enable) {
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading" role="tab">';
    echo '<h4 class="panel-title">'.$title.'</h4>';
    echo '</div>';
    echo '<div class="panel-collapse collapse in" role="tabpanel">';
    echo '<div class="panel-body">';
    echo '<ul class="nav nav-pills nav-stacked">';
    echo '<li>';
    echo '<a href="'.$pluginPath.'identification-data.php">';
    echo '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/list.png">';
    echo $plugin->get_lang('DataCenter');
    echo '</a>';
    echo '</li>';
    echo '<li>';
    echo '<a href="'.$pluginPath.'formative-actions-list.php">';
    echo '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/forms.png">';
    echo $plugin->get_lang('FormativeActionsForm');
    echo '</a>';
    echo '</li>';
    echo '<li>';
    echo '<a href="'.$pluginPath.'configuration.php">';
    echo '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/settings.png">';
    echo $plugin->get_lang('Setting');
    echo '</a>';
    echo '</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
