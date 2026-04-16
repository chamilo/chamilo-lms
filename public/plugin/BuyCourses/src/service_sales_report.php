<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * List of service sales of the Buy Courses plugin.
 */

use Chamilo\CoreBundle\Framework\Container;
use Throwable;

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
 * Format a monetary amount without breaking the page when the sale has a missing or invalid ISO currency code.
 */
function formatServiceSaleAmount(BuyCoursesPlugin $plugin, float $amount, mixed $isoCode): string
{
    $normalizedIsoCode = strtoupper(trim((string) $isoCode));

    if ('' === $normalizedIsoCode) {
        return number_format($amount, 2, '.', ',');
    }

    try {
        return $plugin->getPriceWithCurrencyFromIsoCode($amount, $normalizedIsoCode);
    } catch (Throwable) {
        return number_format($amount, 2, '.', ',').' '.$normalizedIsoCode;
    }
}

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();
$httpRequest = Container::getRequest();

$paypalEnable = 'true' === $plugin->get('paypal_enable');
$commissionsEnable = 'true' === $plugin->get('commissions_enable');
$includeServices = 'true' === $plugin->get('include_services');
$invoicingEnable = 'true' === $plugin->get('invoicing_enable');

$saleStatuses = $plugin->getServiceSaleStatuses();
$selectedStatus = $httpRequest->query->getInt('status', BuyCoursesPlugin::SERVICE_STATUS_PENDING);
$searchUser = trim((string) $httpRequest->query->get('user', ''));

if (!isset($saleStatuses[$selectedStatus])) {
    $selectedStatus = BuyCoursesPlugin::SERVICE_STATUS_PENDING;
}

$form = new FormValidator('search', 'get');

if ($form->validate()) {
    $selectedStatus = (int) $form->getSubmitValue('status');
    $searchUser = trim((string) $form->getSubmitValue('user'));

    if (!isset($saleStatuses[$selectedStatus])) {
        $selectedStatus = BuyCoursesPlugin::SERVICE_STATUS_PENDING;
    }
}

$form->addSelect('status', $plugin->get_lang('OrderStatus'), $saleStatuses);
$form->addText('user', get_lang('User'), false);
$form->addButtonSearch(get_lang('Search'), 'search');
$form->setDefaults([
    'status' => $selectedStatus,
    'user' => $searchUser,
]);

$servicesSales = $plugin->getServiceSales(0, $selectedStatus);

foreach ($servicesSales as &$sale) {
    $sale['total_discount'] = '';
    $sale['coupon_code'] = '';
    $sale['complete_user_name'] = api_get_person_name(
        $sale['firstname'] ?? '',
        $sale['lastname'] ?? ''
    );
    $sale['status_label'] = $saleStatuses[$sale['status']] ?? ($sale['status'] ?? '');
    $sale['total_price'] = formatServiceSaleAmount(
        $plugin,
        (float) ($sale['price'] ?? 0),
        $sale['iso_code'] ?? null
    );

    if (0.0 !== (float) ($sale['discount_amount'] ?? 0)) {
        $sale['total_discount'] = formatServiceSaleAmount(
            $plugin,
            (float) ($sale['discount_amount'] ?? 0),
            $sale['iso_code'] ?? null
        );
        $sale['coupon_code'] = $plugin->getServiceSaleCouponCode($sale['id']);
    }
}
unset($sale);

if ('' !== $searchUser) {
    $normalizedSearch = api_strtolower($searchUser);

    $servicesSales = array_values(array_filter(
        $servicesSales,
        static function (array $sale) use ($normalizedSearch): bool {
            $haystacks = [
                (string) ($sale['complete_user_name'] ?? ''),
                (string) ($sale['email'] ?? ''),
                (string) ($sale['service_name'] ?? ''),
                (string) ($sale['reference'] ?? ''),
            ];

            foreach ($haystacks as $haystack) {
                if (false !== strpos(api_strtolower($haystack), $normalizedSearch)) {
                    return true;
                }
            }

            return false;
        }
    ));
}

$interbreadcrumb[] = [
    'url' => '../index.php',
    'name' => $plugin->get_lang('plugin_title'),
];

$defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $defaultBackUrl;

$templateName = $plugin->get_lang('SalesReport');

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('page_title', $templateName);
$template->assign('plugin_title', $plugin->get_lang('plugin_title'));
$template->assign('back_url', $backUrl);

$template->assign(
    'export_report_url',
    api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/export_report.php'
);
$template->assign(
    'paypal_payout_url',
    api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/paypal_payout.php'
);
$template->assign(
    'payout_report_url',
    api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/payout_report.php'
);

$template->assign('paypal_enable', $paypalEnable);
$template->assign('commissions_enable', $commissionsEnable);

$template->assign('form', styleBuyCoursesFormHtml($form->returnForm()));
$template->assign('showing_services', true);
$template->assign('services_are_included', $includeServices);
$template->assign('sale_list', $servicesSales);
$template->assign('sales_count', count($servicesSales));

$template->assign('selected_status', $selectedStatus);
$template->assign('selected_status_label', $saleStatuses[$selectedStatus] ?? null);

$template->assign('sale_status_cancelled', BuyCoursesPlugin::SERVICE_STATUS_CANCELLED);
$template->assign('sale_status_pending', BuyCoursesPlugin::SERVICE_STATUS_PENDING);
$template->assign('sale_status_completed', BuyCoursesPlugin::SERVICE_STATUS_COMPLETED);

$template->assign('invoicing_enable', $invoicingEnable);

$content = $template->fetch('BuyCourses/view/service_sales_report.tpl');
$template->assign('content', $content);
$template->display_one_col_template();
