<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * List of pending payments of the Buy Courses plugin.
 */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();
$httpRequest = Container::getRequest();

$paypalEnable = 'true' === $plugin->get('paypal_enable');
$commissionsEnable = 'true' === $plugin->get('commissions_enable');
$includeServices = 'true' === $plugin->get('include_services');
$invoicingEnable = 'true' === $plugin->get('invoicing_enable');

if ($orderId = $httpRequest->query->getInt('order')) {
    $sale = $plugin->getSale($orderId);

    if (empty($sale)) {
        api_not_allowed(true);
    }

    $urlToRedirect = api_get_self().'?';

    switch ((string) $httpRequest->query->get('action')) {
        case 'confirm':
            $plugin->completeSale($sale['id']);
            $plugin->storePayouts($sale['id']);

            Display::addFlash(
                $plugin->getSubscriptionSuccessMessage($sale)
            );

            $urlToRedirect .= http_build_query([
                'status' => BuyCoursesPlugin::SALE_STATUS_COMPLETED,
                'sale' => $sale['id'],
            ]);

            break;

        case 'cancel':
            $plugin->cancelSale($sale['id']);

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

$selectedStatus = $httpRequest->query->getInt(
    'status',
    BuyCoursesPlugin::SALE_STATUS_PENDING
);
$selectedSale = $httpRequest->query->getInt('sale');
$dateStart = (string) $httpRequest->query->get(
    'date_start',
    date('Y-m-d 00:00', mktime(0, 0, 0))
);
$dateEnd = (string) $httpRequest->query->get(
    'date_end',
    date('Y-m-d 23:59', mktime(23, 59, 59))
);
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
    'user' => $searchTerm,
    'email' => $email,
]);

$sales = [];

switch ($selectedFilterType) {
    case '0':
        $sales = $plugin->getSaleListByStatus($selectedStatus);
        break;

    case '1':
        $sales = $plugin->getSaleListByUser($searchTerm);
        break;

    case '2':
        $sales = $plugin->getSaleListByDate($dateStart, $dateEnd);
        break;

    case '3':
        $sales = $plugin->getSaleListByEmail($email);
        break;
}

foreach ($sales as &$sale) {
    $sale['product_type'] = $productTypes[$sale['product_type']] ?? $sale['product_type'];
    $sale['payment_type'] = $paymentTypes[$sale['payment_type']] ?? $sale['payment_type'];
    $sale['complete_user_name'] = api_get_person_name($sale['firstname'], $sale['lastname']);
    $sale['num_invoice'] = $plugin->getNumInvoice($sale['id'], 0);
    $sale['total_price'] = $plugin->getPriceWithCurrencyFromIsoCode($sale['price'], $sale['iso_code']);

    if (isset($sale['discount_amount']) && 0 != $sale['discount_amount']) {
        $sale['total_discount'] = $plugin->getPriceWithCurrencyFromIsoCode(
            $sale['discount_amount'],
            $sale['iso_code']
        );
        $sale['coupon_code'] = $plugin->getSaleCouponCode($sale['id']);
    }
}
unset($sale);

$interbreadcrumb[] = [
    'url' => '../index.php',
    'name' => $plugin->get_lang('plugin_title'),
];

$htmlHeadXtra[] = BuyCoursesPlugin::getSalesReportScript($sales, $invoicingEnable);

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

$template->assign('form', $form->returnForm());
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

$content = $template->fetch('BuyCourses/view/sales_report.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
