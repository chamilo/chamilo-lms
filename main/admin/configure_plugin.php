<?php

/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2012
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$pluginName = $_GET['name'];
$appPlugin = new AppPlugin();
$installedPlugins = $appPlugin->getInstalledPlugins();
$pluginInfo = $appPlugin->getPluginInfo($pluginName, true);

if (!in_array($pluginName, $installedPlugins) || empty($pluginInfo)) {
    api_not_allowed(true);
}

$content = '';
$currentUrl = api_get_self()."?name=$pluginName";

if (isset($pluginInfo['settings_form'])) {
    /** @var FormValidator $form */
    $form = $pluginInfo['settings_form'];
    if (isset($form)) {
        // We override the form attributes
        $attributes = ['action' => $currentUrl, 'method' => 'POST'];
        $form->updateAttributes($attributes);
        if (isset($pluginInfo['settings'])) {
            $form->setDefaults($pluginInfo['settings']);
        }
        $content = Display::page_header($pluginInfo['title']);
        $content .= $form->toHtml();
    }
} else {
    Display::addFlash(
        Display::return_message(get_lang('NoConfigurationSettingsForThisPlugin'), 'warning')
    );
}

if (isset($form)) {
    if ($form->validate()) {
        $values = $form->getSubmitValues();

        // Fix only for bbb
        if ($pluginName === 'bbb') {
            if (!isset($values['global_conference_allow_roles'])) {
                $values['global_conference_allow_roles'] = [];
            }
        }

        $accessUrlId = api_get_current_access_url_id();
        api_delete_settings_params(
            [
                'category = ? AND access_url = ? AND subkey = ? AND type = ? and variable <> ?' => [
                    'Plugins',
                    $accessUrlId,
                    $pluginName,
                    'setting',
                    'status',
                ],
            ]
        );

        foreach ($values as $key => $value) {
            api_add_setting(
                $value,
                $pluginName.'_'.$key,
                $pluginName,
                'setting',
                'Plugins',
                $pluginName,
                '',
                '',
                '',
                $accessUrlId,
                1
            );
        }

        Event::addEvent(
            LOG_PLUGIN_CHANGE,
            LOG_PLUGIN_SETTINGS_CHANGE,
            $pluginName,
            api_get_utc_datetime()
        );
        api_flush_settings_cache($accessUrlId);

        if (!empty($pluginInfo['plugin_class'])) {
            /** @var \Plugin $objPlugin */
            $objPlugin = $pluginInfo['plugin_class']::create();
            $objPlugin->get_settings(true);
            $objPlugin->performActionsAfterConfigure();

            if (isset($values['show_main_menu_tab'])) {
                $objPlugin->manageTab($values['show_main_menu_tab']);
            }
        }

        Display::addFlash(Display::return_message(get_lang('Updated'), 'success'));
        header("Location: $currentUrl");
        exit;
    } else {
        foreach ($form->_errors as $error) {
            Display::addFlash(Display::return_message($error, 'error'));
        }
    }
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('PlatformAdmin'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins',
    'name' => get_lang('Plugins'),
];

$tpl = new Template($pluginName, true, true, false, true, false);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
