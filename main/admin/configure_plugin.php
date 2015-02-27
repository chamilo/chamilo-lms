<?php
/* For licensing terms, see /license.txt */
/**
	@author Julio Montoya <gugli100@gmail.com> BeezNest 2012
*	@package chamilo.admin
*/
use \ChamiloSession as Session;

// name of the language file that needs to be included
$language_file = array ('registration','admin');
$cidReset = true;
require_once '../inc/global.inc.php';

// Access restrictions
api_protect_admin_script();

$plugin_name = $_GET['name'];

$plugin_obj = new AppPlugin();
$installed_plugins = $plugin_obj->get_installed_plugins();
$plugin_info = $plugin_obj->getPluginInfo($plugin_name, true);

if (!in_array($plugin_name, $installed_plugins) || empty($plugin_info)) {
    api_not_allowed(true);
}

global $_configuration;
$message = null;
$content = null;

$currentURL = api_get_self() . "?name=$plugin_name";

if (isset($plugin_info['settings_form'])) {
    $form = $plugin_info['settings_form'];
    if (isset($form)) {
        //We override the form attributes
        $attributes = array('action'=>$currentURL, 'method'=>'POST');
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
            api_add_setting(
                $value,
                Database::escape_string($plugin_name.'_'.$key),
                $plugin_name,
                'setting',
                'Plugins',
                $plugin_name,
                null,
                null,
                null,
                $_configuration['access_url'],
                1
            );
        }
        if (isset($values['show_main_menu_tab'])) {
            $objPlugin = $plugin_info['plugin_class']::create();
            $objPlugin->manageTab($values['show_main_menu_tab']);
        }
        $message = Display::return_message(get_lang('Updated'), 'success');

        Session::write('message', $message);
        
        header("Location: $currentURL");
        exit;
    }
}

if (Session::has('message')) {
    $message = Session::read('message');
}

$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins', 'name' => get_lang('Plugins'));

$tpl = new Template($plugin_name, true, true, false, true, false);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();

Session::erase('message');
