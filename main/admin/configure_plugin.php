<?php
/* For licensing terms, see /license.txt */
/**
	@author Julio Montoya <gugli100@gmail.com> BeezNest 2012
*	@package chamilo.admin
*/

// name of the language file that needs to be included
$language_file = array ('registration','admin');
$cidReset = true;
require_once '../inc/global.inc.php';

// Access restrictions
api_protect_admin_script();

$plugin_name = $_GET['name'];

$plugin_obj = new AppPlugin();
$plugin_info = $plugin_obj->get_plugin_info($plugin_name, true);

if (empty($plugin_info)) {
    api_not_allowed();
}

$installed_plugins = $plugin_obj->get_installed_plugins();

if (!in_array($plugin_name, $installed_plugins)) {
    api_not_allowed();
}

global $_configuration;

$content = null;

if (isset($plugin_info['settings_form'])) {
    $form = $plugin_info['settings_form'];    
    if (isset($form)) {
        //We override the form attributes
        $attributes = array('action'=>api_get_self().'?name='.$plugin_name, 'method'=>'POST');
        $form->updateAttributes($attributes);
        $content = Display::page_header($plugin_info['title']);
        $content .= $form->toHtml();
    }
} else {
    $message = Display::return_message(get_lang('NoConfigurationSettingsForThisPlugin'), 'warning');
}

if (isset($form)) {
    if ($form->validate()) {
        $values = $form->exportValues();
        
        //api_delete_category_settings_by_subkey($plugin_name);
        $access_url_id = api_get_current_access_url_id();
        api_delete_settings_params(array('category = ? AND access_url = ? AND subkey = ? AND type = ? and variable <> ?' =>
                                    array('Plugins', $access_url_id, $plugin_name, 'setting', "status")));
        foreach ($values as $key => $value) {
            $key = Database::escape_string($plugin_name.'_'.$key);
            api_add_setting($value, $key, $plugin_name, 'setting', 'Plugins', $plugin_name, null, null, null, $_configuration['access_url'], 1);
            
        }
        $message = Display::return_message(get_lang('Updated'), 'success');
    }
}
$tpl = new Template($tool_name, true, true, false, true, false);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();