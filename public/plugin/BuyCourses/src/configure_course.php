<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/*
 * Configuration script for the Buy Courses plugin.
 */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once '../config.php';

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
 * Style the legacy FormValidator markup with Tailwind utility classes.
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

    $formNodeList = $xpath->query('.//form', $root);
    $form = ($formNodeList && $formNodeList->length > 0) ? $formNodeList->item(0) : null;

    if ($form instanceof DOMElement) {
        addTailwindClassesToElement($form, ['space-y-6']);
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
                'space-y-3',
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
                'mb-2',
                'block',
                'text-sm',
                'font-semibold',
                'text-gray-90',
            ]);
        }
    }

    $columns = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " col-sm-2 ") or contains(concat(" ", normalize-space(@class), " "), " col-sm-8 ") or contains(concat(" ", normalize-space(@class), " "), " col-sm-10 ") or contains(concat(" ", normalize-space(@class), " "), " col-sm-offset-2 ")]',
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

            if (in_array($type, ['hidden'], true)) {
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

            $classes = [
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
            ];

            if ($select->hasAttribute('multiple')) {
                $classes[] = 'min-h-56';
            }

            addTailwindClassesToElement($select, $classes);
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

    $helpBlocks = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " help-block ") or contains(concat(" ", normalize-space(@class), " "), " form-control-feedback ")]',
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

    $alerts = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " alert ")]',
        $root
    );
    if ($alerts) {
        foreach ($alerts as $alert) {
            if (!$alert instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($alert, [
                'rounded-2xl',
                'border',
                'border-info/20',
                'bg-support-2',
                'px-4',
                'py-3',
                'text-sm',
                'text-gray-90',
            ]);
        }
    }

    $checkboxContainers = $xpath->query(
        './/*[contains(concat(" ", normalize-space(@class), " "), " checkbox ")]',
        $root
    );
    if ($checkboxContainers) {
        foreach ($checkboxContainers as $checkboxContainer) {
            if (!$checkboxContainer instanceof DOMElement) {
                continue;
            }

            addTailwindClassesToElement($checkboxContainer, [
                'flex',
                'items-center',
                'gap-3',
            ]);
        }
    }

    $buttons = $xpath->query('.//button | .//input[@type="submit"] | .//input[@type="button"]', $root);
    if ($buttons) {
        foreach ($buttons as $button) {
            if (!$button instanceof DOMElement) {
                continue;
            }

            $buttonClasses = [
                'inline-flex',
                'items-center',
                'justify-center',
                'gap-2',
                'rounded-xl',
                'px-4',
                'py-2.5',
                'text-sm',
                'font-semibold',
                'shadow-sm',
                'transition',
                'focus:outline-none',
                'focus:ring-2',
                'focus:ring-offset-2',
            ];

            $isPrimaryAction = 'submit' === strtolower((string) $button->getAttribute('type'))
                || false !== stripos((string) $button->getAttribute('class'), 'save')
                || false !== stripos((string) $button->textContent, 'save');

            if ($isPrimaryAction) {
                $buttonClasses = array_merge($buttonClasses, [
                    'bg-success',
                    'text-white',
                    'hover:opacity-90',
                    'focus:ring-success/30',
                ]);
            } else {
                $buttonClasses = array_merge($buttonClasses, [
                    'bg-warning',
                    'text-white',
                    'hover:opacity-90',
                    'focus:ring-warning/30',
                ]);
            }

            addTailwindClassesToElement($button, $buttonClasses);
        }
    }

    $result = getElementInnerHtml($root);

    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrors);

    return $result;
}

api_protect_admin_script();

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$type = isset($_REQUEST['type']) ? (int) $_REQUEST['type'] : 0;

if (empty($id) || empty($type)) {
    api_not_allowed();
}

$plugin = BuyCoursesPlugin::create();
$commissionsEnable = $plugin->get('commissions_enable');

if ('true' == $commissionsEnable) {
    $htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_PLUGIN_PATH)
        .'BuyCourses/resources/js/commissions.js"></script>';
    $commissions = '';
}

$includeSession = 'true' === $plugin->get('include_sessions');
$editingCourse = BuyCoursesPlugin::PRODUCT_TYPE_COURSE === $type;
$editingSession = BuyCoursesPlugin::PRODUCT_TYPE_SESSION === $type;

$entityManager = Database::getManager();
$userRepo = Container::getUserRepository();
$currency = $plugin->getSelectedCurrency();

if (empty($currency)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('CurrencyIsNotConfigured'), 'error')
    );
    $currency = null;
}

$currencyIso = null;

$defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/list.php';

if ($editingSession) {
    $defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/list_session.php';
}

$backUrl = $defaultBackUrl;
$paymentSetupUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/paymentsetup.php';

if ($editingCourse) {
    $course = $entityManager->find(Course::class, $id);

    if (!$course) {
        api_not_allowed(true);
    }

    /*if (!$plugin->isValidCourse($course)) {
        api_not_allowed(true);
    }*/

    $courseItem = $plugin->getCourseForConfiguration($course, $currency);
    $defaultBeneficiaries = [];
    $teachers = $course->getTeachersSubscriptions();
    $teachersOptions = [];

    foreach ($teachers as $courseTeacher) {
        $teacher = $courseTeacher->getUser();
        $teachersOptions[] = [
            'text' => $teacher->getFullName(),
            'value' => $teacher->getId(),
        ];
        $defaultBeneficiaries[] = $teacher->getId();
    }

    if (!empty($courseItem['item_id'])) {
        $currentBeneficiaries = $plugin->getItemBeneficiaries($courseItem['course_id']);

        if (!empty($currentBeneficiaries)) {
            $defaultBeneficiaries = array_column($currentBeneficiaries, 'user_id');

            if ('true' === $commissionsEnable) {
                $defaultCommissions = array_column($currentBeneficiaries, 'commissions');

                foreach ($defaultCommissions as $defaultCommission) {
                    $commissions .= $defaultCommission.',';
                }

                $commissions = substr($commissions, 0, -1);
            }
        }

        $currencyIso = $courseItem['currency'];
        $formDefaults = [
            'product_type' => get_lang('Course'),
            'id' => $courseItem['course_id'],
            'type' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
            'name' => $courseItem['course_title'],
            'visible' => $courseItem['visible'],
            'price' => $courseItem['price'],
            'tax_perc' => $courseItem['tax_perc'],
            'beneficiaries' => $defaultBeneficiaries,
        ];
    } else {
        $formDefaults = [
            'product_type' => get_lang('Course'),
            'id' => $courseItem['course_id'],
            'type' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
            'name' => $courseItem['course_title'],
            'visible' => false,
            'price' => 0,
            'tax_perc' => 0,
            'beneficiaries' => [],
        ];
    }

    if ('true' == $commissionsEnable) {
        $formDefaults['commissions'] = $commissions;
    }
} elseif ($editingSession) {
    if (!$includeSession) {
        api_not_allowed(true);
    }

    $session = $entityManager->find(Session::class, $id);

    if (!$session) {
        api_not_allowed(true);
    }

    $sessionItem = $plugin->getSessionForConfiguration($session, $currency);
    $generalCoaches = $session->getGeneralCoaches();
    $generalCoachesOptions = [];
    $defaultBeneficiaries = [];

    foreach ($generalCoaches as $generalCoach) {
        $generalCoachesOptions[] = [
            'text' => $generalCoach->getFullName(),
            'value' => $generalCoach->getId(),
        ];
        $defaultBeneficiaries[] = $generalCoach->getId();
    }

    $courseCoachesOptions = [];
    $sessionCourses = $session->getCourses();

    foreach ($sessionCourses as $sessionCourse) {
        $courseCoaches = $userRepo->getCoachesForSessionCourse($session, $sessionCourse->getCourse());

        foreach ($courseCoaches as $courseCoach) {
            if ($session->hasUserAsGeneralCoach($courseCoach)) {
                continue;
            }

            $courseCoachesOptions[] = [
                'text' => $courseCoach->getFullName(),
                'value' => $courseCoach->getId(),
            ];
            $defaultBeneficiaries[] = $courseCoach->getId();
        }
    }

    $currentBeneficiaries = [];

    if ($sessionItem['item_id']) {
        $currentBeneficiaries = $plugin->getItemBeneficiaries($sessionItem['item_id']);
    }

    if (!empty($currentBeneficiaries)) {
        $defaultBeneficiaries = array_column($currentBeneficiaries, 'user_id');

        if ('true' == $commissionsEnable) {
            $defaultCommissions = array_column($currentBeneficiaries, 'commissions');

            foreach ($defaultCommissions as $defaultCommission) {
                $commissions .= $defaultCommission.',';
            }

            $commissions = substr($commissions, 0, -1);
        }
    }

    $currencyIso = $sessionItem['currency'];
    $formDefaults = [
        'product_type' => get_lang('Session'),
        'id' => $session->getId(),
        'type' => BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
        'name' => $sessionItem['session_name'],
        'visible' => $sessionItem['visible'],
        'price' => $sessionItem['price'],
        'tax_perc' => $sessionItem['tax_perc'],
        'beneficiaries' => $defaultBeneficiaries,
    ];

    if ('true' == $commissionsEnable) {
        $formDefaults['commissions'] = $commissions;
    }
} else {
    api_not_allowed(true);
}

if ('true' === $commissionsEnable) {
    $htmlHeadXtra[] = "
        <script>
            $(function() {
                if ($('[name=\"commissions\"]').val() === '') {
                    $('#panelSliders').html(
                        '<button id=\"setCommissionsButton\" type=\"button\" class=\"inline-flex items-center justify-center gap-2 rounded-xl bg-warning px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-warning/30 focus:ring-offset-2\">'
                            + '".get_plugin_lang('SetCommissions', 'BuyCoursesPlugin')."'
                            + '</button>'
                    );
                } else {
                    showSliders(100, 'default', '".$commissions."');
                }

                var maxPercentage = 100;

                $('#selectBox').on('change', function() {
                    $('#panelSliders').html('');
                });

                $(document).on('click', '#setCommissionsButton', function() {
                    $('#panelSliders').html('');
                    showSliders(maxPercentage, 'renew');
                });
            });
        </script>
    ";
}

$globalSettingsParams = $plugin->getGlobalParameters();

$form = new FormValidator('beneficiaries');
$form->addText('product_type', $plugin->get_lang('ProductType'), false);
$form->addText('name', get_lang('Name'), false);
$form->addCheckBox(
    'visible',
    $plugin->get_lang('VisibleInCatalog'),
    $plugin->get_lang('ShowOnCourseCatalog')
);
$form->addElement(
    'number',
    'price',
    [$plugin->get_lang('Price'), null, $currencyIso],
    ['step' => 0.01]
);
$form->addElement(
    'number',
    'tax_perc',
    [$plugin->get_lang('TaxPerc'), $plugin->get_lang('TaxPercDescription'), '%'],
    ['step' => 1, 'placeholder' => $globalSettingsParams['global_tax_perc'].'% '.$plugin->get_lang('ByDefault')]
);

$beneficiariesSelect = $form->addSelect(
    'beneficiaries',
    $plugin->get_lang('Beneficiaries'),
    null,
    ['multiple' => 'multiple', 'id' => 'selectBox']
);

if ($editingCourse) {
    $teachersOptions = api_unique_multidim_array($teachersOptions, 'value');
    $beneficiariesSelect->addOptGroup($teachersOptions, get_lang('Teachers'));
} elseif ($editingSession) {
    $courseCoachesOptions = api_unique_multidim_array($courseCoachesOptions, 'value');
    $beneficiariesSelect->addOptGroup($generalCoachesOptions, get_lang('Session general coaches'));
    $beneficiariesSelect->addOptGroup($courseCoachesOptions, get_lang('SessionCourseCoach'));
}

if ('true' === $commissionsEnable) {
    $platformCommission = $plugin->getPlatformCommission();
    $form->addHtml(
        '
        <div class="form-group">
            <label for="sliders" class="col-sm-2 control-label">
                '.get_plugin_lang('Commissions', 'BuyCoursesPlugin').'
            </label>
            <div class="col-sm-8">
                '.Display::return_message(
            sprintf($plugin->get_lang('TheActualPlatformCommissionIsX'), $platformCommission['commission'].'%'),
            'info',
            false
        ).'
                <div id="panelSliders"></div>
            </div>
        </div>'
    );
    $form->addHidden('commissions', '');
}

$form->addHidden('type', null);
$form->addHidden('id', null);

$button = $form->addButtonSave(get_lang('Save'));

if (empty($currency)) {
    $button->setAttribute('disabled');
}

$form->freeze(['product_type', 'name']);

if ($form->validate()) {
    $formValues = $form->exportValues();
    $id = (int) $formValues['id'];
    $type = (int) $formValues['type'];

    $productItem = $plugin->getItemByProduct($id, $type);

    if (isset($formValues['visible'])) {
        $taxPerc = '' != $formValues['tax_perc'] ? (int) $formValues['tax_perc'] : null;

        if (!empty($productItem)) {
            $plugin->updateItem(
                [
                    'price' => (float) $formValues['price'],
                    'tax_perc' => $taxPerc,
                ],
                $id,
                $type
            );
        } else {
            $itemId = $plugin->registerItem([
                'currency_id' => (int) $currency['id'],
                'product_type' => $type,
                'product_id' => $id,
                'price' => (float) $_POST['price'],
                'tax_perc' => $taxPerc,
            ]);
            $productItem['id'] = $itemId;
        }

        $plugin->deleteItemBeneficiaries($productItem['id']);

        if (isset($formValues['beneficiaries'])) {
            if ('true' === $commissionsEnable) {
                $usersId = $formValues['beneficiaries'];
                $commissions = explode(',', $formValues['commissions']);
                $commissions = (count($usersId) != count($commissions))
                    ? array_fill(0, count($usersId), 0)
                    : $commissions;
                $beneficiaries = array_combine($usersId, $commissions);
            } else {
                $usersId = $formValues['beneficiaries'];
                $commissions = array_fill(0, count($usersId), 0);
                $beneficiaries = array_combine($usersId, $commissions);
            }

            $plugin->registerItemBeneficiaries($productItem['id'], $beneficiaries);
        }
    } elseif (!empty($productItem['id'])) {
        $plugin->deleteItem($productItem['id']);
    }

    $url = 'list.php';

    if (2 == $type) {
        $url = 'list_session.php';
    }

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/'.$url);
    exit;
}

$form->setDefaults($formDefaults);

$templateName = '';
$interbreadcrumb[] = [
    'url' => 'paymentsetup.php',
    'name' => get_lang('Configuration'),
];

switch ($type) {
    case 2:
        $interbreadcrumb[] = [
            'url' => 'list_session.php',
            'name' => $plugin->get_lang('Sessions'),
        ];
        $templateName = $plugin->get_lang('Sessions');

        break;

    default:
        $interbreadcrumb[] = [
            'url' => 'list.php',
            'name' => $plugin->get_lang('Available Courses'),
        ];
        $templateName = $plugin->get_lang('Available course');
}

$formHtml = styleBuyCoursesFormHtml($form->returnForm());

$productLabel = htmlspecialchars((string) ($formDefaults['product_type'] ?? ''), ENT_QUOTES, 'UTF-8');
$productName = htmlspecialchars((string) ($formDefaults['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$currencyLabel = htmlspecialchars((string) ($currencyIso ?: get_lang('None')), ENT_QUOTES, 'UTF-8');

$pageDescription = $editingCourse
    ? 'Configure how this course appears in the catalog, set the selling price and choose the beneficiaries.'
    : 'Configure how this session appears in the catalog, set the selling price and choose the beneficiaries.';

$content = '
<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
                    '.htmlspecialchars($plugin->get_lang('plugin_title'), ENT_QUOTES, 'UTF-8').'
                </div>

                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
                        '.htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8').'
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-50">
                        '.$pageDescription.'
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a
                    href="'.htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8').'"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-white px-4 py-2.5 text-sm font-semibold text-gray-90 transition hover:border-primary/30 hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2"
                >
                    <em class="fa fa-arrow-left fa-fw"></em>
                    '.htmlspecialchars(get_lang('Back'), ENT_QUOTES, 'UTF-8').'
                </a>

                <a
                    href="'.htmlspecialchars($paymentSetupUrl, ENT_QUOTES, 'UTF-8').'"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                >
                    <em class="fa fa-credit-card fa-fw"></em>
                    '.htmlspecialchars($plugin->get_lang('PaymentsConfiguration'), ENT_QUOTES, 'UTF-8').'
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    '.htmlspecialchars($plugin->get_lang('ProductType'), ENT_QUOTES, 'UTF-8').'
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    '.$productLabel.'
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4 md:col-span-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    '.htmlspecialchars(get_lang('Name'), ENT_QUOTES, 'UTF-8').'
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    '.$productName.'
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    '.htmlspecialchars(get_lang('Currency'), ENT_QUOTES, 'UTF-8').'
                </div>
                <div class="mt-2 text-base font-semibold text-gray-90">
                    '.$currencyLabel.'
                </div>
            </div>

            <div class="rounded-2xl border border-gray-25 bg-support-2 p-4 md:col-span-2">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-50">
                    '.htmlspecialchars(get_lang('Information'), ENT_QUOTES, 'UTF-8').'
                </div>
                <div class="mt-2 text-sm leading-6 text-gray-90">
                    Enable catalog visibility to keep this product available for sale, set the price, and define the beneficiaries who receive the related commissions or earnings.
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-gray-25 bg-gray-10 p-6 shadow-sm">
        '.$formHtml.'
    </section>
</div>';

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
