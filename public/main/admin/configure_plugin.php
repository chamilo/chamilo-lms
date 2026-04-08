<?php

/* For licensing terms, see /license.txt */

/**
 * Plugin configuration page.
 *
 * This page renders the dynamic settings form declared by each plugin and
 * persists the configuration for the current access URL.
 */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

/**
 * Add classes to a DOM element without removing existing classes.
 */
function c2_add_tailwind_classes_to_element(DOMElement $element, array $classes): void
{
    $existing = trim((string) $element->getAttribute('class'));
    $currentClasses = '' === $existing ? [] : preg_split('/\s+/', $existing);
    $currentClasses = is_array($currentClasses) ? $currentClasses : [];

    foreach ($classes as $class) {
        if (!in_array($class, $currentClasses, true)) {
            $currentClasses[] = $class;
        }
    }

    $element->setAttribute('class', trim(implode(' ', array_filter($currentClasses))));
}

/**
 * Return the inner HTML of a DOM element.
 */
function c2_get_element_inner_html(DOMElement $element): string
{
    $html = '';

    foreach ($element->childNodes as $childNode) {
        $html .= $element->ownerDocument->saveHTML($childNode);
    }

    return $html;
}

/**
 * Style plugin settings forms with Tailwind utility classes.
 */
function c2_style_plugin_settings_form_html(string $html): string
{
    if (!class_exists(DOMDocument::class) || '' === trim($html)) {
        return $html;
    }

    $previousUseInternalErrors = libxml_use_internal_errors(true);

    $document = new DOMDocument('1.0', 'UTF-8');
    $wrappedHtml = '<?xml encoding="utf-8" ?><div id="plugin-settings-form-root">'.$html.'</div>';

    $loaded = $document->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    if (!$loaded) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $xpath = new DOMXPath($document);
    $root = $document->getElementById('plugin-settings-form-root');

    if (!$root) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $forms = $xpath->query('.//form', $root);
    if ($forms) {
        foreach ($forms as $form) {
            if (!$form instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($form, ['space-y-6']);
        }
    }

    $fieldsets = $xpath->query('.//fieldset', $root);
    if ($fieldsets) {
        foreach ($fieldsets as $fieldset) {
            if (!$fieldset instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($fieldset, [
                'rounded-lg',
                'border',
                'border-gray-30',
                'bg-white',
                'p-6',
                'shadow-sm',
                'space-y-4',
            ]);
        }
    }

    $legends = $xpath->query('.//legend', $root);
    if ($legends) {
        foreach ($legends as $legend) {
            if (!$legend instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($legend, [
                'px-2',
                'text-sm',
                'font-semibold',
                'text-gray-90',
            ]);
        }
    }

    $formGroups = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " form-group ")]', $root);
    if ($formGroups) {
        foreach ($formGroups as $group) {
            if (!$group instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($group, [
                'rounded-lg',
                'border',
                'border-gray-30',
                'bg-white',
                'p-5',
                'shadow-sm',
                'space-y-3',
            ]);
        }
    }

    $columns = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " col-sm-2 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-3 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-4 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-5 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-6 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-7 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-8 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-9 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-10 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-11 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-12 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-offset-2 ")]',
        $root
    );

    if ($columns) {
        foreach ($columns as $column) {
            if (!$column instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($column, ['w-full', 'max-w-none']);
        }
    }

    $checkboxContainers = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " checkbox ")
            or contains(concat(" ", normalize-space(@class), " "), " radio ")]',
        $root
    );
    if ($checkboxContainers) {
        foreach ($checkboxContainers as $container) {
            if (!$container instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($container, [
                'flex',
                'items-center',
                'gap-3',
            ]);
        }
    }

    $helpBlocks = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " help-block ")
            or contains(concat(" ", normalize-space(@class), " "), " form-control-feedback ")]',
        $root
    );
    if ($helpBlocks) {
        foreach ($helpBlocks as $helpBlock) {
            if (!$helpBlock instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($helpBlock, [
                'mt-2',
                'block',
                'text-sm',
                'text-gray-50',
            ]);
        }
    }

    $tables = $xpath->query('.//table', $root);
    if ($tables) {
        foreach ($tables as $table) {
            if (!$table instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($table, [
                'min-w-full',
                'divide-y',
                'divide-gray-25',
            ]);
        }
    }

    $theadList = $xpath->query('.//thead', $root);
    if ($theadList) {
        foreach ($theadList as $thead) {
            if (!$thead instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($thead, ['bg-gray-15']);
        }
    }

    $thList = $xpath->query('.//th', $root);
    if ($thList) {
        foreach ($thList as $th) {
            if (!$th instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($th, [
                'px-4',
                'py-3',
                'text-left',
                'text-xs',
                'font-semibold',
                'uppercase',
                'tracking-wide',
                'text-gray-50',
            ]);
        }
    }

    $tdList = $xpath->query('.//td', $root);
    if ($tdList) {
        foreach ($tdList as $td) {
            if (!$td instanceof DOMElement) {
                continue;
            }

            c2_add_tailwind_classes_to_element($td, [
                'px-4',
                'py-4',
                'align-middle',
                'text-sm',
                'text-gray-90',
            ]);
        }
    }

    $result = c2_get_element_inner_html($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $result;
}

/**
 * Optional best-effort loader for a plugin object.
 *
 * Returns a Plugin instance or null.
 */
function try_load_plugin_obj(string $title): ?Plugin
{
    $obj = Container::getPluginHelper()->loadLegacyPlugin($title);

    if ($obj instanceof Plugin) {
        return $obj;
    }

    $sysPath = api_get_path(SYS_PLUGIN_PATH);
    $studly = implode('', array_map('ucfirst', preg_split('/[^a-z0-9]+/i', $title)));
    $dirs = array_unique([$title, strtolower($title), ucfirst(strtolower($title)), $studly]);

    foreach ($dirs as $dir) {
        $base = $sysPath.$dir.'/';
        $classS = implode('', array_map('ucfirst', preg_split('/[^a-z0-9]+/i', (string) $dir)));
        $classes = array_unique([$dir, $dir.'Plugin', $classS, $classS.'Plugin']);

        foreach ($classes as $cls) {
            $paths = [$base.'src/'.$cls.'.php', $base.$cls.'.php'];

            foreach ($paths as $path) {
                if (!is_readable($path)) {
                    continue;
                }

                require_once $path;

                if (class_exists($cls) && method_exists($cls, 'create')) {
                    $maybe = $cls::create();

                    if ($maybe instanceof Plugin) {
                        return $maybe;
                    }
                }
            }
        }
    }

    return null;
}

/**
 * Return field names that only control plugin state and should not be exposed
 * as configurable settings in this page.
 */
function plugin_get_toggle_field_names(): array
{
    return [
        'tool_enable',
        'enable_onlyoffice_plugin',
        'enabled',
        'enable',
        'active',
        'is_active',
    ];
}

/**
 * Remove state toggle fields recursively from submitted values.
 */
function plugin_remove_toggle_fields_from_values(array &$values): void
{
    $toggleFields = plugin_get_toggle_field_names();

    foreach ($values as $key => &$value) {
        if (in_array((string) $key, $toggleFields, true)) {
            unset($values[$key]);
            continue;
        }

        if (is_array($value)) {
            plugin_remove_toggle_fields_from_values($value);
        }
    }
}

/**
 * Find the most appropriate container node for a form field.
 */
function plugin_find_form_field_container(DOMNode $node, DOMElement $root): ?DOMNode
{
    $current = $node;

    while ($current && $current !== $root) {
        if ($current instanceof DOMElement) {
            $class = ' '.trim((string) $current->getAttribute('class')).' ';

            if (
                str_contains($class, ' form-group ') ||
                str_contains($class, ' checkbox ') ||
                str_contains($class, ' radio ')
            ) {
                return $current;
            }

            if (in_array(strtolower($current->tagName), ['tr', 'li', 'p', 'fieldset'], true)) {
                return $current;
            }
        }

        $current = $current->parentNode;
    }

    return null;
}

/**
 * Remove state toggle fields from the rendered form HTML.
 */
function plugin_remove_toggle_fields_from_form_html(string $html): string
{
    if (!class_exists(DOMDocument::class) || '' === trim($html)) {
        return $html;
    }

    $previousUseInternalErrors = libxml_use_internal_errors(true);

    $document = new DOMDocument('1.0', 'UTF-8');
    $wrappedHtml = '<?xml encoding="utf-8" ?><div id="plugin-settings-clean-root">'.$html.'</div>';

    $loaded = $document->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    if (!$loaded) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $xpath = new DOMXPath($document);
    $root = $document->getElementById('plugin-settings-clean-root');

    if (!$root) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $toggleFields = plugin_get_toggle_field_names();
    $nodesToRemove = [];

    $fields = $xpath->query('.//*[@name]', $root);

    if ($fields) {
        foreach ($fields as $field) {
            if (!$field instanceof DOMElement) {
                continue;
            }

            $fieldName = (string) $field->getAttribute('name');
            $normalizedFieldName = preg_replace('/(\[\])+$/', '', $fieldName);

            if (!in_array($normalizedFieldName, $toggleFields, true)) {
                continue;
            }

            $container = plugin_find_form_field_container($field, $root);

            if ($container instanceof DOMNode) {
                $nodesToRemove[spl_object_hash($container)] = $container;
            } elseif ($field->parentNode instanceof DOMNode) {
                $nodesToRemove[spl_object_hash($field)] = $field;
            }
        }
    }

    foreach ($nodesToRemove as $nodeToRemove) {
        if ($nodeToRemove->parentNode) {
            $nodeToRemove->parentNode->removeChild($nodeToRemove);
        }
    }

    $cleanHtml = c2_get_element_inner_html($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $cleanHtml;
}

$pluginRepo = Container::getPluginRepository();
$pluginName = isset($_GET['plugin']) ? (string) $_GET['plugin'] : '';
$plugin = $pluginRepo->getInstalledByName($pluginName);

if (!$plugin || !$plugin->isInstalled()) {
    api_not_allowed(true);
}

$accessUrl = Container::getAccessUrlUtil()->getCurrent();
$pluginConfiguration = $plugin->getConfigurationsByAccessUrl($accessUrl);

$appPlugin = new AppPlugin();
$pluginInfo = $appPlugin->getPluginInfo($plugin->getTitle(), true) ?? [];
$prevDefaultVis = $pluginInfo['settings']['defaultVisibilityInCourseHomepage'] ?? null;

$pluginObj = $pluginInfo['obj'] ?? null;
if (!$pluginObj instanceof Plugin) {
    $pluginObj = try_load_plugin_obj($plugin->getTitle());

    if ($pluginObj instanceof Plugin) {
        $pluginInfo['obj'] = $pluginObj;
    }
}

/** @var Plugin|null $objPlugin */
$objPlugin = $pluginInfo['obj'] ?? null;

$em = Container::getEntityManager();
$pluginHelper = Container::getPluginHelper();

$currentUrl = api_get_self().'?plugin='.$plugin->getTitle();
$backUrl = api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins';

$declaredFieldNames = $objPlugin instanceof Plugin
    ? $objPlugin->getFieldNames()
    : (isset($pluginInfo['settings']) && is_array($pluginInfo['settings']) ? array_keys($pluginInfo['settings']) : []);

$toggleFields = plugin_get_toggle_field_names();
$editableFieldNames = array_values(array_diff($declaredFieldNames, $toggleFields));
$hasEditableFields = count($editableFieldNames) > 0;
$isEnabledNow = $pluginConfiguration?->isActive() ?? false;

$form = null;
$styledFormHtml = '';

if (isset($pluginInfo['settings_form']) && $hasEditableFields) {
    /** @var FormValidator $form */
    $form = $pluginInfo['settings_form'];

    if (!empty($form)) {
        $form->updateAttributes([
            'action' => $currentUrl,
            'method' => 'POST',
        ]);

        if (isset($pluginInfo['settings']) && is_array($pluginInfo['settings'])) {
            foreach ($toggleFields as $toggleField) {
                unset($pluginInfo['settings'][$toggleField]);
            }

            $storedDefaults = array_filter(
                $pluginInfo['settings'],
                static fn ($value): bool => null !== $value
            );

            $form->setDefaults($storedDefaults);
        }

        if ($form->validate()) {
            $values = $form->getSubmitValues();

            if ('bbb' === $plugin->getTitle() && !isset($values['global_conference_allow_roles'])) {
                $values['global_conference_allow_roles'] = [];
            }

            plugin_remove_toggle_fields_from_values($values);

            $formName = $form->getAttribute('name') ?: 'form';
            $reservedKeys = ['submit', 'submit_button', '_token', '_qf__'.$formName];

            foreach ($reservedKeys as $reservedKey) {
                if (isset($values[$reservedKey])) {
                    unset($values[$reservedKey]);
                }
            }

            if ($objPlugin instanceof Plugin) {
                $pluginFields = $objPlugin->getFieldNames();
                $toPersist = array_intersect_key($values, array_flip($pluginFields));
            } elseif (!empty($pluginInfo['settings']) && is_array($pluginInfo['settings'])) {
                $whitelist = array_keys($pluginInfo['settings']);
                $toPersist = array_intersect_key($values, array_flip($whitelist));
            } else {
                $toPersist = $values;
            }

            foreach ($toggleFields as $toggleField) {
                unset($toPersist[$toggleField]);
            }

            if (!$pluginConfiguration) {
                $pluginConfiguration = $plugin->getOrCreatePluginConfiguration($accessUrl);
            }

            $currentConfiguration = $pluginConfiguration->getConfiguration();
            if (!is_array($currentConfiguration)) {
                $currentConfiguration = [];
            }

            $preservedConfiguration = $currentConfiguration;

            foreach ($editableFieldNames as $editableFieldName) {
                unset($preservedConfiguration[$editableFieldName]);
            }

            $newConfiguration = array_merge($preservedConfiguration, $toPersist);

            $pluginConfiguration->setConfiguration($newConfiguration);
            $em->flush();

            Event::addEvent(
                LOG_PLUGIN_CHANGE,
                LOG_PLUGIN_SETTINGS_CHANGE,
                $plugin->getTitle(),
                api_get_utc_datetime(),
                api_get_user_id()
            );

            $newDefaultVis = $values['defaultVisibilityInCourseHomepage'] ?? $prevDefaultVis;

            if ($objPlugin instanceof Plugin) {
                $isEnabledNow = $pluginConfiguration->isActive();
                $objPlugin->get_settings(true);

                if (!empty($objPlugin->isCoursePlugin) && $isEnabledNow) {
                    if ($newDefaultVis !== $prevDefaultVis) {
                        $objPlugin->uninstall_course_fields_in_all_courses();
                        $objPlugin->install_course_fields_in_all_courses();
                    }
                }

                $objPlugin->performActionsAfterConfigure();

                if (isset($values['show_main_menu_tab']) && $objPlugin instanceof Plugin) {
                    $showMainMenuTab = filter_var(
                        $values['show_main_menu_tab'],
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    );

                    $objPlugin->manageTab(true === $showMainMenuTab);
                }
            }

            Display::addFlash(Display::return_message(get_lang('Update successful'), 'success'));
            header("Location: $currentUrl");
            exit;
        }

        foreach ($form->_errors as $error) {
            Display::addFlash(Display::return_message($error, 'error'));
        }

        $styledFormHtml = c2_style_plugin_settings_form_html($form->toHtml());
        $styledFormHtml = plugin_remove_toggle_fields_from_form_html($styledFormHtml);
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

$pluginTitle = $pluginInfo['title'] ?? $plugin->getTitle();
$pluginTitleEscaped = htmlspecialchars((string) $pluginTitle, ENT_QUOTES, 'UTF-8');
$pluginNameEscaped = htmlspecialchars((string) $plugin->getTitle(), ENT_QUOTES, 'UTF-8');
$pluginStatusLabel = $isEnabledNow ? get_lang('Enabled') : get_lang('Disabled');

$settingsCountLabel = (string) count($editableFieldNames);
$hasSettingsLabel = $hasEditableFields ? get_lang('Yes') : get_lang('No');
$pluginSummaryText = $hasEditableFields
    ? 'This plugin exposes configurable settings for the current access URL.'
    : 'This plugin does not expose configurable settings. Activation is managed from the plugins list.';

$content = '
<div class="section-header section-header--h2">
    <h2 class="section-header__title">'.$pluginTitleEscaped.'</h2>
    <div class="section-header__actions">
        <a href="'.$backUrl.'" class="btn btn--plain-outline">
            <em class="mdi mdi-arrow-left"></em>
            '.htmlspecialchars(get_lang('Back to plugins'), ENT_QUOTES, 'UTF-8').'
        </a>
    </div>
</div>
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-lg border border-gray-30 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-x-6 gap-y-3 lg:flex-row lg:items-start lg:justify-between">
            <p class="text-body-1 text-gray-50">'.$pluginSummaryText.'</p>
            <div class="badge badge--default">
                '.htmlspecialchars(get_lang('Plugins'), ENT_QUOTES, 'UTF-8').'
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-30 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    '.htmlspecialchars(get_lang('Name'), ENT_QUOTES, 'UTF-8').'
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    '.$pluginNameEscaped.'
                </div>
            </div>

            <div class="rounded-lg border border-gray-30 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    '.htmlspecialchars(get_lang('Status'), ENT_QUOTES, 'UTF-8').'
                </div>
                <div class="mt-2">
                    <span class="badge '.($isEnabledNow ? 'badge--success' : 'badge--default').'">
                        '.htmlspecialchars($pluginStatusLabel, ENT_QUOTES, 'UTF-8').'
                    </span>
                </div>
            </div>

            <div class="rounded-lg border border-gray-30 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    '.htmlspecialchars(get_lang('Settings'), ENT_QUOTES, 'UTF-8').'
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    '.$settingsCountLabel.'
                </div>
            </div>

            <div class="rounded-lg border border-gray-30 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    '.htmlspecialchars(get_lang('Configuration'), ENT_QUOTES, 'UTF-8').'
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    '.htmlspecialchars($hasSettingsLabel, ENT_QUOTES, 'UTF-8').'
                </div>
            </div>
        </div>
    </section>';

if ($hasEditableFields && '' !== trim($styledFormHtml)) {
    $content .= '
    <section class="rounded-lg border border-gray-30 bg-gray-10 p-6 shadow-sm">
        '.$styledFormHtml.'
    </section>';
} else {
    $content .= '
    <section class="rounded-lg border border-gray-30 bg-white p-6 shadow-sm">
        <div class="rounded-lg border border-info/20 bg-support-2 px-4 py-4 text-sm text-gray-90">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 text-primary">
                    <em class="fa fa-info-circle text-lg"></em>
                </div>
                <div>
                    <p class="font-semibold text-gray-90">'.htmlspecialchars(get_lang('Information'), ENT_QUOTES, 'UTF-8').'</p>
                    <p class="mt-1 leading-6 text-gray-90">
                        '.htmlspecialchars(get_lang('This plugin has no configurable settings. Activation is managed from the plugins list.'), ENT_QUOTES, 'UTF-8').'
                    </p>
                </div>
            </div>
        </div>
    </section>';
}

$content .= '</div>';

$tpl = new Template($plugin->getTitle(), true, true, false, true, false);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
