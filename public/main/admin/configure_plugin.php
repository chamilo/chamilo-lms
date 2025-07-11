<?php

/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2012
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$pluginRepo = Container::getPluginRepository();

$plugin = $pluginRepo->getInstalledByName($_GET['plugin']);

if (!$plugin) {
    api_not_allowed(true);
}

$accessUrl = Container::getAccessUrlUtil()->getCurrent();

$pluginConfiguration = $plugin->getConfigurationsByAccessUrl($accessUrl);

$appPlugin = new AppPlugin();
$pluginInfo = $appPlugin->getPluginInfo($plugin->getTitle(), true);

$em = Container::getEntityManager();

$content = '';
$currentUrl = api_get_self()."?plugin={$plugin->getTitle()}";

if (isset($pluginInfo['settings_form'])) {
    /** @var FormValidator $form */
    $form = $pluginInfo['settings_form'];
    if (!empty($form)) {
        $form->updateAttributes(['action' => $currentUrl, 'method' => 'POST']);
        if (isset($pluginInfo['settings'])) {
            $form->setDefaults($pluginInfo['settings']);
        }
        $content = Display::page_header($pluginInfo['title']);
        $content .= $form->toHtml();
    }
} else {
    Display::addFlash(
        Display::return_message(get_lang('No configuration settings found for this plugin'), 'warning')
    );
}

if (isset($form)) {
    if ($form->validate()) {
        $values = $form->getSubmitValues();

        // Fix only for bbb
        if ('bbb' == $plugin->getTitle() && !isset($values['global_conference_allow_roles'])) {
            $values['global_conference_allow_roles'] = [];
        }

        /** @var Plugin $objPlugin */
        $objPlugin = $pluginInfo['obj'];

        /** @var array<int, string> $pluginFields */
        $pluginFields = $objPlugin->getFieldNames();

        $pluginConfiguration->setConfiguration(
            array_intersect_key($values, array_flip($pluginFields))
        );

        $em->flush();

        Event::addEvent(
            LOG_PLUGIN_CHANGE,
            LOG_PLUGIN_SETTINGS_CHANGE,
            $plugin->getTitle(),
            api_get_utc_datetime(),
            api_get_user_id()
        );

        $objPlugin->performActionsAfterConfigure();

        if (isset($values['show_main_menu_tab'])) {
            $objPlugin->manageTab($values['show_main_menu_tab']);
        }

        Display::addFlash(Display::return_message(get_lang('Update successful'), 'success'));
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
    'name' => get_lang('Administration'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins',
    'name' => get_lang('Plugins'),
];

$tpl = new Template($plugin->getTitle(), true, true, false, true, false);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
