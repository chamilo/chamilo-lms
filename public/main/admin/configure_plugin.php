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

$pluginConfiguration = $plugin->getOrCreatePluginConfiguration($accessUrl);

$appPlugin = new AppPlugin();
$pluginInfo = $appPlugin->getPluginInfo($plugin->getTitle(), true);
$prevDefaultVis = $pluginInfo['settings']['defaultVisibilityInCourseHomepage'] ?? null;
$prevToolEnable = $pluginInfo['settings']['tool_enable'] ?? null;

$em = Container::getEntityManager();

$currentUrl = api_get_self().'?plugin='.$plugin->getTitle();
$backUrl    = api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins';

$headerHtml = '
<div class="mb-4 flex items-center justify-between">
  <h2 class="text-2xl font-semibold text-gray-90">'.htmlspecialchars($pluginInfo['title'] ?? $plugin->getTitle(), ENT_QUOTES).'</h2>
  <a href="'.$backUrl.'" class="btn btn--sm btn--plain" title="'.get_lang('Back').'">
    <i class="mdi mdi-arrow-left"></i> '.get_lang('Back to plugins').'
  </a>
</div>
';

$content = $headerHtml;

if (isset($pluginInfo['settings_form'])) {
    /** @var FormValidator $form */
    $form = $pluginInfo['settings_form'];
    if (!empty($form)) {
        $form->updateAttributes(['action' => $currentUrl, 'method' => 'POST']);
        if (isset($pluginInfo['settings'])) {
            $form->setDefaults($pluginInfo['settings']);
        }
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

        $newDefaultVis = $values['defaultVisibilityInCourseHomepage'] ?? $prevDefaultVis;
        $newToolEnable = $values['tool_enable'] ?? $prevToolEnable;
        $toBool = fn($v) => in_array($v, ['1', 1, true, 'true', 'on'], true);
        $prevEnabled = $toBool($prevToolEnable);
        $newEnabled  = $toBool($newToolEnable);

        $objPlugin->get_settings(true);
        if (!empty($objPlugin->isCoursePlugin)) {
            $didToggle = ($newEnabled !== $prevEnabled);
            if ($didToggle) {
                if ($newEnabled) {
                    $objPlugin->install_course_fields_in_all_courses();
                } else {
                    $objPlugin->uninstall_course_fields_in_all_courses();
                }
            } elseif ($newEnabled && $newDefaultVis !== $prevDefaultVis) {
                $objPlugin->uninstall_course_fields_in_all_courses();
                $objPlugin->install_course_fields_in_all_courses();
            }
        }

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
