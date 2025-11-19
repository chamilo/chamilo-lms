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

$appPlugin  = new AppPlugin();
$pluginInfo = $appPlugin->getPluginInfo($plugin->getTitle(), true) ?? [];
$prevDefaultVis = $pluginInfo['settings']['defaultVisibilityInCourseHomepage'] ?? null;
$prevToolEnable = $pluginInfo['settings']['tool_enable'] ?? null;

/**
 * Optional best-effort loader for legacy plugin object.
 * Returns a Plugin instance or null. We DO NOT abort if null.
 */
function c2_try_load_legacy_plugin_obj(string $title): ?Plugin
{
    // Try helper first (handles title normalization through repositories)
    $obj = Container::getPluginHelper()->loadLegacyPlugin($title);
    if ($obj instanceof Plugin) {
        return $obj;
    }

    // Minimal extra attempts with common casings and suffix
    $sysPath  = api_get_path(SYS_PLUGIN_PATH);
    $studly   = implode('', array_map('ucfirst', preg_split('/[^a-z0-9]+/i', $title)));
    $dirs     = array_unique([$title, strtolower($title), ucfirst(strtolower($title)), $studly]);
    foreach ($dirs as $dir) {
        $base   = $sysPath.$dir.'/';
        $classS = implode('', array_map('ucfirst', preg_split('/[^a-z0-9]+/i', (string)$dir)));
        $classes = array_unique([$dir, $dir.'Plugin', $classS, $classS.'Plugin']);
        foreach ($classes as $cls) {
            $paths = [$base.'src/'.$cls.'.php', $base.$cls.'.php'];
            foreach ($paths as $p) {
                if (is_readable($p)) {
                    require_once $p;
                    if (class_exists($cls) && method_exists($cls, 'create')) {
                        $maybe = $cls::create();
                        if ($maybe instanceof Plugin) {
                            return $maybe;
                        }
                    }
                }
            }
        }
    }

    return null;
}

// Try to ensure we have a Plugin instance, but DO NOT hard-fail if we can't.
$legacyObj = $pluginInfo['obj'] ?? null;
if (!$legacyObj instanceof Plugin) {
    $legacyObj = c2_try_load_legacy_plugin_obj($plugin->getTitle());
    if ($legacyObj instanceof Plugin) {
        $pluginInfo['obj'] = $legacyObj;
    }
}

/** @var Plugin|null $objPlugin */
$objPlugin = $pluginInfo['obj'] ?? null;

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

/**
 * Decide if there are editable settings to render.
 * - Remove legacy 'tool_enable' from the list before deciding.
 */
$declaredFieldNames =
    $objPlugin instanceof Plugin
        ? $objPlugin->getFieldNames()
        : (isset($pluginInfo['settings']) && is_array($pluginInfo['settings']) ? array_keys($pluginInfo['settings']) : []);

$editableFieldNames = array_values(array_diff($declaredFieldNames, ['tool_enable']));
$hasEditableFields  = \count($editableFieldNames) > 0;

if (isset($pluginInfo['settings_form']) && $hasEditableFields) {
    /** @var FormValidator $form */
    $form = $pluginInfo['settings_form'];
    if (!empty($form)) {
        // Ensure proper form action and method
        $form->updateAttributes(['action' => $currentUrl, 'method' => 'POST']);

        // Drop legacy toggle from defaults, so it won't get posted accidentally
        if (isset($pluginInfo['settings'])) {
            unset($pluginInfo['settings']['tool_enable']);
            $form->setDefaults($pluginInfo['settings']);
        }

        // Best-effort: hide legacy 'tool_enable' if some plugin still injects it
        $content .= '<style>
            [name="tool_enable"],
            input[name*="[tool_enable]"],
            select[name*="[tool_enable]"],
            label[for="tool_enable"] { display:none !important; }
        </style>';

        $content .= $form->toHtml();
    }
} else {
    // No editable settings: show a small info block (no empty form, no Save button).
    $content .= '
    <div class="rounded-md border border-gray-20 bg-gray-05 p-4 text-sm text-gray-80">
      <div class="font-semibold mb-1">'.get_lang('Information').'</div>
      <p class="m-0">'.get_lang('This plugin has no configurable settings. Activation is managed from the plugins list.').'</p>
    </div>';
}

if (isset($form) && $hasEditableFields) {
    if ($form->validate()) {
        $values = $form->getSubmitValues();

        // Fix only for bbb (keep previous behavior)
        if ('bbb' == $plugin->getTitle() && !isset($values['global_conference_allow_roles'])) {
            $values['global_conference_allow_roles'] = [];
        }

        // Never persist legacy toggle even if posted by mistake (deep filter)
        $stripToolEnable = function (&$arr) use (&$stripToolEnable) {
            if (!is_array($arr)) { return; }
            foreach ($arr as $k => &$v) {
                if ($k === 'tool_enable') {
                    unset($arr[$k]);
                    continue;
                }
                if (is_array($v)) {
                    $stripToolEnable($v);
                }
            }
        };
        $stripToolEnable($values);

        // Reserved/irrelevant keys we don't want to persist
        $formName = method_exists($form, 'getAttribute') ? ($form->getAttribute('name') ?: 'form') : 'form';
        $reservedKeys = ['submit', 'submit_button', '_token', '_qf__'.$formName];
        foreach ($reservedKeys as $rk) {
            if (isset($values[$rk])) {
                unset($values[$rk]);
            }
        }

        // Build safe whitelist
        if ($objPlugin instanceof Plugin) {
            /** @var array<int, string> $pluginFields */
            $pluginFields = $objPlugin->getFieldNames();
            $toPersist = array_intersect_key($values, array_flip($pluginFields));
        } elseif (!empty($pluginInfo['settings']) && is_array($pluginInfo['settings'])) {
            $whitelist = array_keys($pluginInfo['settings']);
            $toPersist = array_intersect_key($values, array_flip($whitelist));
        } else {
            $toPersist = $values;
        }

        // Persist configuration JSON
        $pluginConfiguration->setConfiguration($toPersist);
        $em->flush();

        Event::addEvent(
            LOG_PLUGIN_CHANGE,
            LOG_PLUGIN_SETTINGS_CHANGE,
            $plugin->getTitle(),
            api_get_utc_datetime(),
            api_get_user_id()
        );

        // Compute only visibility delta; activation is managed from the plugins list (C2 source of truth)
        $newDefaultVis = $values['defaultVisibilityInCourseHomepage'] ?? $prevDefaultVis;

        // Re-seed course icons only if plugin object exists, is course plugin, and is currently active
        if ($objPlugin instanceof Plugin) {
            $isEnabledNow = Container::getPluginHelper()->isPluginEnabled($plugin->getTitle());
            $objPlugin->get_settings(true);

            if (!empty($objPlugin->isCoursePlugin) && $isEnabledNow) {
                if ($newDefaultVis !== $prevDefaultVis) {
                    // Recreate links to match new default visibility
                    $objPlugin->uninstall_course_fields_in_all_courses();
                    $objPlugin->install_course_fields_in_all_courses();
                }
            }

            // Allow plugins to run post-config hooks
            $objPlugin->performActionsAfterConfigure();

            // Optional tab management (preserve existing behavior)
            if (isset($values['show_main_menu_tab'])) {
                $objPlugin->manageTab($values['show_main_menu_tab']);
            }
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
