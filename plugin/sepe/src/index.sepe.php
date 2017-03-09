<?php
/* For license terms, see /license.txt */
/**
 * Index of the Sepe plugin
 * @package chamilo.plugin.sepe
 */
/**
 *
 */
$plugin = SepePlugin::create();

$is_enable = $plugin->get('sepe_enable');
$title="Administraci&oacute;n SEPE";
$pluginPath = api_get_path(WEB_PLUGIN_PATH).'sepe/src/';
if (api_is_platform_admin() && $is_enable=="true") {
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading" role="tab">';
    echo '<h4 class="panel-title">'.$title.'</h4>';
    echo '</div>';
    echo '<div class="panel-collapse collapse in" role="tabpanel">';
    echo '<div class="panel-body">';
    echo '<ul class="nav nav-pills nav-stacked">';
    echo '<li>';
    echo '<a href="'.$pluginPath.'datos-identificativos.php">';
        echo '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/list.png">';
        echo $plugin->get_lang('datos_centro');
    echo '</a>';
    echo '</li>';
    echo '<li>';
    echo '<a href="'.$pluginPath.'listado-acciones-formativas.php">';
        echo '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/forms.png">';
        echo $plugin->get_lang('formulario_acciones_formativas');
    echo '</a>';
    echo '</li>';
    echo '<li>';
    echo '<a href="'.$pluginPath.'configuracion.php">';
        echo '<img src="'.api_get_path(WEB_PLUGIN_PATH).'sepe/resources/settings.png">';
        echo $plugin->get_lang('configuracion_sepe');
    echo '</a>';
    echo '</li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

