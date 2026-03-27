<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Configuration script for the Buy Courses plugin.
 */
require_once '../config.php';

/**
 * Add classes to a DOM element without removing existing classes.
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
function styleBuyCoursesCouponFormHtml(string $html): string
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
            if ($form instanceof DOMElement) {
                addTailwindClassesToElement($form, ['space-y-6']);
            }
        }
    }

    $formGroups = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " form-group ")]', $root);
    if ($formGroups) {
        foreach ($formGroups as $group) {
            if ($group instanceof DOMElement) {
                addTailwindClassesToElement($group, [
                    'rounded-2xl',
                    'border',
                    'border-gray-25',
                    'bg-white',
                    'p-5',
                    'shadow-sm',
                    'space-y-3',
                ]);
            }
        }
    }

    $labels = $xpath->query('.//label', $root);
    if ($labels) {
        foreach ($labels as $label) {
            if ($label instanceof DOMElement) {
                addTailwindClassesToElement($label, [
                    'mb-2',
                    'block',
                    'text-sm',
                    'font-semibold',
                    'text-gray-90',
                ]);
            }
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
                    'bg-primary',
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
                    'focus:ring-primary/30',
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
            if ($select instanceof DOMElement) {
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
    }

    $buttons = $xpath->query('.//button', $root);
    if ($buttons) {
        foreach ($buttons as $button) {
            if ($button instanceof DOMElement) {
                addTailwindClassesToElement($button, [
                    'inline-flex',
                    'items-center',
                    'justify-center',
                    'gap-2',
                    'rounded-xl',
                    'bg-primary',
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
                    'focus:ring-primary/30',
                    'focus:ring-offset-2',
                ]);
            }
        }
    }

    $helpBlocks = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " help-block ")
            or contains(concat(" ", normalize-space(@class), " "), " form-control-feedback ")]',
        $root
    );
    if ($helpBlocks) {
        foreach ($helpBlocks as $helpBlock) {
            if ($helpBlock instanceof DOMElement) {
                addTailwindClassesToElement($helpBlock, [
                    'mt-2',
                    'block',
                    'text-sm',
                    'text-gray-50',
                ]);
            }
        }
    }

    $advmultiselects = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " advmultiselect ")]',
        $root
    );
    if ($advmultiselects) {
        foreach ($advmultiselects as $multiselect) {
            if (!$multiselect instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($multiselect, [
                'grid',
                'gap-4',
                'lg:grid-cols-[minmax(0,1fr)_72px_minmax(0,1fr)]',
                'items-start',
            ]);

            $internalSelects = $xpath->query('.//select', $multiselect);
            if ($internalSelects) {
                foreach ($internalSelects as $internalSelect) {
                    if ($internalSelect instanceof DOMElement) {
                        addTailwindClassesToElement($internalSelect, [
                            'min-h-[240px]',
                            'w-full',
                            'rounded-2xl',
                            'border',
                            'border-gray-25',
                            'bg-white',
                            'p-3',
                            'text-sm',
                            'text-gray-90',
                            'shadow-sm',
                        ]);
                    }
                }
            }

            $internalInputs = $xpath->query('.//input', $multiselect);
            if ($internalInputs) {
                foreach ($internalInputs as $internalInput) {
                    if (!$internalInput instanceof DOMElement) {
                        continue;
                    }

                    $type = strtolower((string) $internalInput->getAttribute('type'));

                    if ('hidden' === $type) {
                        continue;
                    }

                    addTailwindClassesToElement($internalInput, [
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

            $internalButtons = $xpath->query('.//button|.//input[@type="button"]|.//input[@type="submit"]', $multiselect);
            if ($internalButtons) {
                foreach ($internalButtons as $internalButton) {
                    if ($internalButton instanceof DOMElement) {
                        addTailwindClassesToElement($internalButton, [
                            'inline-flex',
                            'h-10',
                            'w-full',
                            'items-center',
                            'justify-center',
                            'rounded-xl',
                            'bg-primary',
                            'px-3',
                            'text-sm',
                            'font-semibold',
                            'text-white',
                            'shadow-sm',
                            'transition',
                            'hover:opacity-90',
                        ]);
                    }
                }
            }
        }
    }

    $result = getElementInnerHtml($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $result;
}

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

$includeSession = 'true' === $plugin->get('include_sessions');
$includeServices = 'true' === $plugin->get('include_services');

$currency = $plugin->getSelectedCurrency();

if (empty($currency)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('CurrencyIsNotConfigured'), 'error')
    );
}

$currencyIso = $currency['iso_code'] ?? null;

$courses = [];
$sessions = [];
$services = [];

$coursesList = CourseManager::get_courses_list(
    0,
    0,
    'title',
    'asc',
    -1,
    null,
    api_get_current_access_url_id(),
    false,
    [],
    []
);

foreach ($coursesList as $course) {
    $courseId = isset($course['id']) ? (int) $course['id'] : 0;
    $courseTitle = isset($course['title']) ? trim((string) $course['title']) : '';

    if ($courseId <= 0 || '' === $courseTitle) {
        continue;
    }

    $courses[$courseId] = $courseTitle;
}

$sessionsList = SessionManager::get_sessions_list(
    [],
    [],
    null,
    null,
    api_get_current_access_url_id(),
    []
);

foreach ($sessionsList as $session) {
    $sessionId = isset($session['id']) ? (int) $session['id'] : 0;

    $sessionName = '';
    if (isset($session['name']) && '' !== trim((string) $session['name'])) {
        $sessionName = trim((string) $session['name']);
    } elseif (isset($session['session_name']) && '' !== trim((string) $session['session_name'])) {
        $sessionName = trim((string) $session['session_name']);
    } elseif (isset($session['title']) && '' !== trim((string) $session['title'])) {
        $sessionName = trim((string) $session['title']);
    } elseif (isset($session['name_and_dates']) && '' !== trim((string) $session['name_and_dates'])) {
        $sessionName = trim((string) $session['name_and_dates']);
    }

    if ($sessionId <= 0 || '' === $sessionName) {
        continue;
    }

    $sessions[$sessionId] = $sessionName;
}

$servicesList = $plugin->getAllServices();

foreach ($servicesList as $service) {
    $serviceId = isset($service['id']) ? (int) $service['id'] : 0;
    $serviceName = isset($service['name']) ? trim((string) $service['name']) : '';

    if ($serviceId <= 0 || '' === $serviceName) {
        continue;
    }

    $services[$serviceId] = $serviceName;
}

$discountTypes = $plugin->getCouponDiscountTypes();

// Build the form.
$form = new FormValidator('add_coupon');
$form->addText('code', $plugin->get_lang('CouponCode'), true);
$form->addRadio('discount_type', $plugin->get_lang('CouponDiscountType'), $discountTypes);
$form->addElement(
    'number',
    'discount_amount',
    [$plugin->get_lang('CouponDiscount'), null, $currencyIso],
    ['step' => 1, 'min' => 0]
);
$form->addDateRangePicker('date', get_lang('Date'), true);
$form->addCheckBox('active', get_lang('Active'));
$form->addElement(
    'advmultiselect',
    'courses',
    get_lang('Courses'),
    $courses,
    []
);

if ($includeSession) {
    $form->addElement(
        'advmultiselect',
        'sessions',
        get_lang('Sessions'),
        $sessions,
        []
    );
}

if ($includeServices) {
    $form->addElement(
        'advmultiselect',
        'services',
        get_lang('Services'),
        $services,
        []
    );
}

$button = $form->addButtonSave(get_lang('Save'));

if (empty($currency)) {
    $button->setAttribute('disabled');
}

if ($form->validate()) {
    $formValues = $form->exportValues();

    $coupon = [];
    $coupon['code'] = isset($formValues['code']) ? trim((string) $formValues['code']) : '';
    $coupon['discount_type'] = isset($formValues['discount_type']) ? (int) $formValues['discount_type'] : 0;
    $coupon['discount_amount'] = isset($formValues['discount_amount']) ? (float) $formValues['discount_amount'] : 0;
    $coupon['valid_start'] = isset($formValues['date_start']) ? (string) $formValues['date_start'] : '';
    $coupon['valid_end'] = isset($formValues['date_end']) ? (string) $formValues['date_end'] : '';
    $coupon['active'] = !empty($formValues['active']) ? 1 : 0;

    if (
        BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE === $coupon['discount_type'] &&
        $coupon['discount_amount'] > 100
    ) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('CouponDiscountExceed100'), 'error', false)
        );

        header('Location: '.api_get_self());
        exit;
    }

    $coupon['courses'] = isset($formValues['courses']) && is_array($formValues['courses'])
        ? array_values(array_filter(array_map('intval', $formValues['courses']), static fn (int $id): bool => $id > 0))
        : [];

    $coupon['sessions'] = isset($formValues['sessions']) && is_array($formValues['sessions'])
        ? array_values(array_filter(array_map('intval', $formValues['sessions']), static fn (int $id): bool => $id > 0))
        : [];

    $coupon['services'] = isset($formValues['services']) && is_array($formValues['services'])
        ? array_values(array_filter(array_map('intval', $formValues['services']), static fn (int $id): bool => $id > 0))
        : [];

    $result = $plugin->addNewCoupon($coupon);

    if ($result) {
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/coupons.php');
    } else {
        header('Location: '.api_get_self());
    }

    exit;
}

$formDefaults = [
    'code' => '',
    'discount_type' => null,
    'discount_amount' => 0,
    'active' => 0,
    'courses' => [],
    'sessions' => [],
    'services' => [],
];

$form->setDefaults($formDefaults);

$templateName = $plugin->get_lang('CouponAdd');
$interbreadcrumb[] = [
    'url' => 'paymentsetup.php',
    'name' => get_lang('Configuration'),
];
$interbreadcrumb[] = [
    'url' => 'coupons.php',
    'name' => $plugin->get_lang('CouponList'),
];

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('page_title', $templateName);
$template->assign('plugin_title', $plugin->get_lang('plugin_title'));
$template->assign('back_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/coupons.php');
$template->assign('form', styleBuyCoursesCouponFormHtml($form->returnForm()));
$content = $template->fetch('BuyCourses/view/coupon_add.tpl');
$template->assign('content', $content);
$template->display_one_col_template();
