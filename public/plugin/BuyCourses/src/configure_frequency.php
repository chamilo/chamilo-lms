<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Configuration page for subscription frequencies for the Buy Courses plugin.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

/**
 * Add classes to an element without removing existing ones.
 */
function addTailwindClassesToElement(DOMElement $element, array $classes): void
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
function getElementInnerHtml(DOMElement $element): string
{
    $html = '';

    foreach ($element->childNodes as $childNode) {
        $html .= $element->ownerDocument->saveHTML($childNode);
    }

    return $html;
}

/**
 * Style legacy FormValidator markup with Tailwind utility classes.
 */
function styleBuyCoursesFormHtml(string $html): string
{
    if (!class_exists(DOMDocument::class) || '' === trim($html)) {
        return $html;
    }

    $previousUseInternalErrors = libxml_use_internal_errors(true);

    $document = new DOMDocument('1.0', 'UTF-8');
    $wrappedHtml = '<?xml encoding="utf-8" ?><div id="buycourses-form-root">'.$html.'</div>';

    $loaded = $document->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    if (!$loaded) {
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return $html;
    }

    $xpath = new DOMXPath($document);
    $root = $document->getElementById('buycourses-form-root');

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

            addTailwindClassesToElement($form, ['space-y-6']);
        }
    }

    $formGroups = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " form-group ")]', $root);
    if ($formGroups) {
        foreach ($formGroups as $group) {
            if (!$group instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($group, [
                'rounded-2xl',
                'border',
                'border-gray-25',
                'bg-white',
                'p-5',
                'shadow-sm',
                'space-y-4',
            ]);
        }
    }

    $labels = $xpath->query('.//label', $root);
    if ($labels) {
        foreach ($labels as $label) {
            if (!$label instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($label, [
                'mb-3',
                'block',
                'text-sm',
                'font-semibold',
                'text-gray-90',
            ]);
        }
    }

    $columns = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " col-sm-2 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-3 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-7 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-8 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-10 ")
            or contains(concat(" ", normalize-space(@class), " "), " col-sm-11 ")]',
        $root
    );

    if ($columns) {
        foreach ($columns as $column) {
            if (!$column instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($column, ['w-full', 'max-w-none']);
        }
    }

    $inputs = $xpath->query('.//input', $root);
    if ($inputs) {
        foreach ($inputs as $input) {
            if (!$input instanceof DOMElement) {
                continue;
            }

            $type = strtolower((string) $input->getAttribute('type'));

            if ('hidden' === $type) {
                continue;
            }

            if (in_array($type, ['checkbox', 'radio'], true)) {
                addTailwindClassesToElement($input, [
                    'h-4',
                    'w-4',
                    'rounded',
                    'border-gray-25',
                    'text-primary',
                    'focus:ring-primary',
                ]);

                continue;
            }

            if (in_array($type, ['submit', 'button'], true)) {
                addTailwindClassesToElement($input, [
                    'inline-flex',
                    'items-center',
                    'justify-center',
                    'gap-2',
                    'rounded-xl',
                    'bg-success',
                    'px-4',
                    'py-2.5',
                    'text-sm',
                    'font-semibold',
                    'text-white',
                    'shadow-sm',
                    'transition',
                    'hover:opacity-90',
                    'focus:outline-none',
                    'focus:ring-2',
                    'focus:ring-success/30',
                    'focus:ring-offset-2',
                ]);

                continue;
            }

            addTailwindClassesToElement($input, [
                'block',
                'w-full',
                'rounded-xl',
                'border-gray-25',
                'bg-white',
                'text-sm',
                'text-gray-90',
                'shadow-sm',
                'placeholder:text-gray-50',
                'focus:border-primary',
                'focus:ring-primary',
            ]);
        }
    }

    $selects = $xpath->query('.//select', $root);
    if ($selects) {
        foreach ($selects as $select) {
            if (!$select instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($select, [
                'block',
                'w-full',
                'rounded-xl',
                'border-gray-25',
                'bg-white',
                'text-sm',
                'text-gray-90',
                'shadow-sm',
                'focus:border-primary',
                'focus:ring-primary',
            ]);
        }
    }

    $textareas = $xpath->query('.//textarea', $root);
    if ($textareas) {
        foreach ($textareas as $textarea) {
            if (!$textarea instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($textarea, [
                'block',
                'w-full',
                'rounded-xl',
                'border-gray-25',
                'bg-white',
                'text-sm',
                'text-gray-90',
                'shadow-sm',
                'placeholder:text-gray-50',
                'focus:border-primary',
                'focus:ring-primary',
            ]);
        }
    }

    $buttons = $xpath->query('.//button');
    if ($buttons) {
        foreach ($buttons as $button) {
            if (!$button instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($button, [
                'inline-flex',
                'items-center',
                'justify-center',
                'gap-2',
                'rounded-xl',
                'bg-success',
                'px-4',
                'py-2.5',
                'text-sm',
                'font-semibold',
                'text-white',
                'shadow-sm',
                'transition',
                'hover:opacity-90',
                'focus:outline-none',
                'focus:ring-2',
                'focus:ring-success/30',
                'focus:ring-offset-2',
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

            addTailwindClassesToElement($helpBlock, [
                'mt-2',
                'block',
                'text-sm',
                'text-gray-50',
            ]);
        }
    }

    $result = getElementInnerHtml($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $result;
}

/**
 * Return a translated plugin label with a safe fallback when the key is missing.
 */
function getPluginLabelWithFallback($plugin, string $key, string $fallback): string
{
    $value = (string) $plugin->get_lang($key);

    if ('' === trim($value) || $value === $key) {
        return $fallback;
    }

    return $value;
}

api_protect_admin_script(true);

$plugin = BuyCoursesPlugin::create();

$subscriptionsListUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscriptions_courses.php';
$backUrl = $subscriptionsListUrl;
$deleteActionUrl = api_get_self();

$frequencyNotRemovedMessage = getPluginLabelWithFallback(
    $plugin,
    'FrequencyNotRemoved',
    'The subscription period could not be removed.'
);
$frequencyInUseMessage = getPluginLabelWithFallback(
    $plugin,
    'SubscriptionPeriodOnUse',
    'This subscription period is currently in use and cannot be deleted.'
);

$deleteAction = (string) ($_POST['action'] ?? '');
$deleteDuration = isset($_POST['duration']) ? (int) $_POST['duration'] : 0;

if ('delete_frequency' === $deleteAction) {
    if ($deleteDuration > 0) {
        $frequency = $plugin->selectFrequency($deleteDuration);

        if (!empty($frequency)) {
            $subscriptionsItems = $plugin->getSubscriptionsItemsByDuration($deleteDuration);
            $usageCount = is_array($subscriptionsItems) ? count($subscriptionsItems) : 0;

            if (0 === $usageCount) {
                $result = $plugin->deleteFrequency($deleteDuration);

                if ($result) {
                    Display::addFlash(
                        Display::return_message($plugin->get_lang('FrequencyRemoved'), 'success')
                    );
                } else {
                    Display::addFlash(
                        Display::return_message($frequencyNotRemovedMessage, 'error')
                    );
                }
            } else {
                Display::addFlash(
                    Display::return_message(
                        sprintf('%s (%d)', $frequencyInUseMessage, $usageCount),
                        'error'
                    )
                );
            }
        } else {
            Display::addFlash(
                Display::return_message($plugin->get_lang('FrequencyNotExits'), 'error')
            );
        }
    } else {
        Display::addFlash(
            Display::return_message($plugin->get_lang('FrequencyIncorrect'), 'error')
        );
    }

    header('Location: '.api_get_self());
    exit;
}

$frequencies = $plugin->getFrequenciesList();

foreach ($frequencies as &$frequency) {
    $duration = isset($frequency['duration']) ? (int) $frequency['duration'] : 0;
    $subscriptionsItems = $plugin->getSubscriptionsItemsByDuration($duration);
    $usageCount = is_array($subscriptionsItems) ? count($subscriptionsItems) : 0;

    $frequency['usage_count'] = $usageCount;
    $frequency['in_use'] = $usageCount > 0;
}
unset($frequency);

$form = new FormValidator('add_frequency');
$form->addText('name', get_lang('Name'), false);
$form->addElement(
    'number',
    'duration',
    [$plugin->get_lang('Duration'), $plugin->get_lang('Days')],
    ['step' => 1, 'min' => 1, 'placeholder' => $plugin->get_lang('SubscriptionFrequencyValueDays')]
);
$form->addButtonSave(get_lang('Save'));

if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    $duration = isset($formValues['duration']) ? (int) $formValues['duration'] : 0;
    $name = isset($formValues['name']) ? trim((string) $formValues['name']) : '';

    if ($duration <= 0 || '' === $name) {
        Display::addFlash(
            Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error')
        );

        header('Location: '.api_get_self());
        exit;
    }

    $frequency = $plugin->selectFrequency($duration);

    if (!empty($frequency)) {
        $result = $plugin->updateFrequency($duration, $name);

        if (!isset($result)) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('FrequencyNotUpdated'), 'error')
            );
        } else {
            Display::addFlash(
                Display::return_message(get_lang('Saved'), 'success')
            );
        }
    } else {
        $result = $plugin->addFrequency($duration, $name);

        if (!isset($result)) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('FrequencyNotSaved'), 'error')
            );
        } else {
            Display::addFlash(
                Display::return_message(get_lang('Saved'), 'success')
            );
        }
    }

    header('Location: '.api_get_self());
    exit;
}

$templateName = $plugin->get_lang('FrequencyAdd');
$interbreadcrumb[] = [
    'url' => 'subscriptions_courses.php',
    'name' => get_lang('Configuration'),
];
$interbreadcrumb[] = [
    'url' => 'subscriptions_courses.php',
    'name' => $plugin->get_lang('SubscriptionList'),
];

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('page_title', $templateName);
$template->assign('plugin_title', $plugin->get_lang('plugin_title'));
$template->assign('back_url', $backUrl);
$template->assign('subscriptions_list_url', $subscriptionsListUrl);
$template->assign('delete_action_url', $deleteActionUrl);
$template->assign('items_form', styleBuyCoursesFormHtml($form->returnForm()));
$template->assign('frequencies_list', $frequencies);
$template->assign('frequencies_count', count($frequencies));

$content = $template->fetch('BuyCourses/view/configure_frequency.tpl');
$template->assign('content', $content);
$template->display_one_col_template();
