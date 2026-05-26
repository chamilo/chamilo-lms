<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * List of pending subscriptions payments of the Buy Courses plugin.
 */

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

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();
$httpRequest = Container::getRequest();

$paypalEnable = 'true' === $plugin->get('paypal_enable');
$commissionsEnable = 'true' === $plugin->get('commissions_enable');
$includeServices = 'true' === $plugin->get('include_services');
$invoicingEnable = 'true' === $plugin->get('invoicing_enable');

if ($orderId = $httpRequest->query->getInt('order')) {
    $sale = $plugin->getSubscriptionSale($orderId);

    if (empty($sale)) {
        api_not_allowed(true);
    }

    $urlToRedirect = api_get_self().'?';

    switch ((string) $httpRequest->query->get('action')) {
        case 'confirm':
            $plugin->completeSubscriptionSale($sale['id']);
            $plugin->storeSubscriptionPayouts($sale['id']);

            Display::addFlash(
                $plugin->getSubscriptionSuccessMessage($sale)
            );

            $urlToRedirect .= http_build_query([
                'status' => BuyCoursesPlugin::SALE_STATUS_COMPLETED,
                'sale' => $sale['id'],
            ]);

            break;

        case 'cancel':
            $plugin->cancelSubscriptionSale($sale['id']);

            Display::addFlash(
                Display::return_message(
                    $plugin->get_lang('OrderCanceled'),
                    'warning'
                )
            );

            $urlToRedirect .= http_build_query([
                'status' => BuyCoursesPlugin::SALE_STATUS_CANCELED,
                'sale' => $sale['id'],
            ]);

            break;
    }

    header("Location: $urlToRedirect");
    exit;
}

$productTypes = $plugin->getProductTypes();
$saleStatuses = $plugin->getSaleStatuses();
$paymentTypes = $plugin->getPaymentTypes();

$allowedFilterTypes = ['0', '1', '2', '3'];

$selectedFilterType = (string) $httpRequest->query->get('filter_type', '0');
if (!in_array($selectedFilterType, $allowedFilterTypes, true)) {
    $selectedFilterType = '0';
}

$selectedStatus = $httpRequest->query->getInt('status', BuyCoursesPlugin::SALE_STATUS_PENDING);
$selectedSale = $httpRequest->query->getInt('sale');
$dateStart = (string) $httpRequest->query->get('date_start', date('Y-m-d H:i', mktime(0, 0, 0)));
$dateEnd = (string) $httpRequest->query->get('date_end', date('Y-m-d H:i', mktime(0, 0, 0)));
$searchTerm = trim((string) $httpRequest->query->get('user', ''));
$email = trim((string) $httpRequest->query->get('email', ''));

$form = new FormValidator('search', 'get');

if ($form->validate()) {
    $submittedFilterType = $form->getSubmitValue('filter_type');
    $selectedFilterType = false === $submittedFilterType ? '0' : (string) $submittedFilterType;

    if (!in_array($selectedFilterType, $allowedFilterTypes, true)) {
        $selectedFilterType = '0';
    }

    $selectedStatus = (int) $form->getSubmitValue('status');
    $searchTerm = trim((string) $form->getSubmitValue('user'));
    $dateStart = (string) $form->getSubmitValue('date_start');
    $dateEnd = (string) $form->getSubmitValue('date_end');
    $email = trim((string) $form->getSubmitValue('email'));

    if (!isset($saleStatuses[$selectedStatus])) {
        $selectedStatus = BuyCoursesPlugin::SALE_STATUS_PENDING;
    }
}

$form->addRadio(
    'filter_type',
    get_lang('Filter'),
    [
        $plugin->get_lang('ByStatus'),
        $plugin->get_lang('ByUser'),
        $plugin->get_lang('ByDate'),
        $plugin->get_lang('ByEmail'),
    ]
);
$form->addHtml('<div id="report-by-status" '.('0' !== $selectedFilterType ? 'style="display:none"' : '').'>');
$form->addSelect('status', $plugin->get_lang('OrderStatus'), $saleStatuses);
$form->addHtml('</div>');
$form->addHtml('<div id="report-by-user" '.('1' !== $selectedFilterType ? 'style="display:none"' : '').'>');
$form->addText('user', get_lang('UserName'), false);
$form->addHtml('</div>');
$form->addHtml('<div id="report-by-date" '.('2' !== $selectedFilterType ? 'style="display:none"' : '').'>');
$form->addDateRangePicker('date', get_lang('Date'), false);
$form->addHtml('</div>');
$form->addHtml('<div id="report-by-email" '.('3' !== $selectedFilterType ? 'style="display:none"' : '').'>');
$form->addText('email', get_lang('Email'), false);
$form->addHtml('</div>');
$form->addButtonFilter(get_lang('Search'));
$form->setDefaults([
    'filter_type' => $selectedFilterType,
    'status' => $selectedStatus,
    'date_start' => $dateStart,
    'date_end' => $dateEnd,
    'email' => $email,
]);

$sales = [];

switch ($selectedFilterType) {
    case '0':
        $sales = $plugin->getSubscriptionSaleListByStatus($selectedStatus);
        break;

    case '1':
        $sales = $plugin->getSubscriptionSaleListByUser($searchTerm);
        break;

    case '2':
        $sales = $plugin->getSubscriptionSaleListByDate($dateStart, $dateEnd);
        break;

    case '3':
        $sales = $plugin->getSubscriptionSaleListByEmail($email);
        break;
}

foreach ($sales as &$sale) {
    $sale['product_type'] = $productTypes[$sale['product_type']] ?? $sale['product_type'];
    $sale['payment_type'] = $paymentTypes[$sale['payment_type']] ?? $sale['payment_type'];
    $sale['complete_user_name'] = api_get_person_name($sale['firstname'], $sale['lastname']);
    $sale['num_invoice'] = $plugin->getNumInvoice($sale['id'], 0);
    $sale['total_price'] = $plugin->getPriceWithCurrencyFromIsoCode($sale['price'], $sale['iso_code']);

    if (isset($sale['discount_amount']) && 0 != $sale['discount_amount']) {
        $sale['total_discount'] = $plugin->getPriceWithCurrencyFromIsoCode($sale['discount_amount'], $sale['iso_code']);
        $sale['coupon_code'] = $plugin->getSaleCouponCode($sale['id']);
    } else {
        $sale['total_discount'] = '';
        $sale['coupon_code'] = '';
    }
}
unset($sale);

$interbreadcrumb[] = [
    'url' => '../index.php',
    'name' => $plugin->get_lang('plugin_title'),
];

$defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $defaultBackUrl;

$filterTypeLabels = [
    '0' => $plugin->get_lang('ByStatus'),
    '1' => $plugin->get_lang('ByUser'),
    '2' => $plugin->get_lang('ByDate'),
    '3' => $plugin->get_lang('ByEmail'),
];

$templateName = $plugin->get_lang('SalesReport');
$template = new Template($templateName);

$template->assign('page_title', $templateName);
$template->assign('plugin_title', $plugin->get_lang('plugin_title'));
$template->assign('back_url', $backUrl);

$template->assign(
    'export_report_url',
    api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/export_subscription_report.php'
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
$template->assign('selected_sale', $selectedSale);
$template->assign('selected_status', $selectedStatus);
$template->assign('selected_status_label', $saleStatuses[$selectedStatus] ?? null);
$template->assign('selected_filter_type', $selectedFilterType);
$template->assign('selected_filter_label', $filterTypeLabels[$selectedFilterType] ?? null);

$template->assign('services_are_included', $includeServices);
$template->assign('sale_list', $sales);
$template->assign('sales_count', count($sales));

$template->assign('sale_status_canceled', BuyCoursesPlugin::SALE_STATUS_CANCELED);
$template->assign('sale_status_pending', BuyCoursesPlugin::SALE_STATUS_PENDING);
$template->assign('sale_status_completed', BuyCoursesPlugin::SALE_STATUS_COMPLETED);

$template->assign('invoicing_enable', $invoicingEnable);
$template->assign('showing_services', false);

$content = $template->fetch('BuyCourses/view/subscription_sales_report.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
