<?php
/* For license terms, see /license.txt */

/**
 * List of pending subscriptions payments of the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

$paypalEnable = $plugin->get('paypal_enable');
$commissionsEnable = $plugin->get('commissions_enable');
$includeServices = $plugin->get('include_services');
$invoicingEnable = $plugin->get('invoicing_enable') === 'true';

if (isset($_GET['order'])) {
    $sale = $plugin->getSubscriptionSale($_GET['order']);

    if (empty($sale)) {
        api_not_allowed(true);
    }

    $urlToRedirect = api_get_self().'?';

    switch ($_GET['action']) {
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

$selectedFilterType = '0';
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : BuyCoursesPlugin::SALE_STATUS_PENDING;
$selectedSale = isset($_GET['sale']) ? intval($_GET['sale']) : 0;
$dateStart = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-d H:i', mktime(0, 0, 0));
$dateEnd = isset($_GET['date_end']) ? $_GET['date_end'] : date('Y-m-d H:i', mktime(23, 59, 59));
$searchTerm = '';
$email = '';

$form = new FormValidator('search', 'get');

if ($form->validate()) {
    $selectedFilterType = $form->getSubmitValue('filter_type');
    $selectedStatus = $form->getSubmitValue('status');
    $searchTerm = $form->getSubmitValue('user');
    $dateStart = $form->getSubmitValue('date_start');
    $dateEnd = $form->getSubmitValue('date_end');
    $email = $form->getSubmitValue('email');

    if ($selectedStatus === false) {
        $selectedStatus = BuyCoursesPlugin::SALE_STATUS_PENDING;
    }

    if ($selectedFilterType === false) {
        $selectedFilterType = '0';
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
$form->addHtml('<div id="report-by-status" '.($selectedFilterType !== '0' ? 'style="display:none"' : '').'>');
$form->addSelect('status', $plugin->get_lang('OrderStatus'), $saleStatuses);
$form->addHtml('</div>');
$form->addHtml('<div id="report-by-user" '.($selectedFilterType !== '1' ? 'style="display:none"' : '').'>');
$form->addText('user', get_lang('UserName'), false);
$form->addHtml('</div>');
$form->addHtml('<div id="report-by-date" '.($selectedFilterType !== '2' ? 'style="display:none"' : '').'>');
$form->addDateRangePicker('date', get_lang('Date'), false);
$form->addHtml('</div>');
$form->addHtml('<div id="report-by-email" '.($selectedFilterType !== '3' ? 'style="display:none"' : '').'>');
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
    $sale['product_type'] = $productTypes[$sale['product_type']];
    $sale['payment_type'] = $paymentTypes[$sale['payment_type']];
    $sale['complete_user_name'] = api_get_person_name($sale['firstname'], $sale['lastname']);
    $sale['num_invoice'] = $plugin->getNumInvoice($sale['id'], 0);
    $sale['total_price'] = $plugin->getPriceWithCurrencyFromIsoCode($sale['price'], $sale['iso_code']);
    if (isset($sale['discount_amount']) && $sale['discount_amount'] != 0) {
        $sale['total_discount'] = $plugin->getPriceWithCurrencyFromIsoCode($sale['discount_amount'], $sale['iso_code']);
        $sale['coupon_code'] = $plugin->getSaleCouponCode($sale['id']);
    }
}

$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('plugin_title')];

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'buycourses/resources/css/style.css');

$templateName = $plugin->get_lang('SalesReport');
$template = new Template($templateName);

$toolbar = Display::url(
    Display::returnFontAwesomeIcon('file-excel-o').
    get_lang('GenerateReport'),
    api_get_path(WEB_PLUGIN_PATH).'buycourses/src/export_subscription_report.php',
    ['class' => 'btn btn-primary']
);

if ($paypalEnable === 'true' && $commissionsEnable === 'true') {
    $toolbar .= Display::toolbarButton(
        $plugin->get_lang('PaypalPayoutCommissions'),
        api_get_path(WEB_PLUGIN_PATH).'buycourses/src/paypal_payout.php',
        'paypal',
        'primary',
        ['title' => $plugin->get_lang('PaypalPayoutCommissions')]
    );

    $template->assign(
        'actions',
        Display::toolbarAction('toolbar', [$toolbar])
    );
}

$template->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);
$template->assign('form', $form->returnForm());
$template->assign('selected_sale', $selectedSale);
$template->assign('selected_status', $selectedStatus);
$template->assign('services_are_included', $includeServices);
$template->assign('sale_list', $sales);
$template->assign('sale_status_canceled', BuyCoursesPlugin::SALE_STATUS_CANCELED);
$template->assign('sale_status_pending', BuyCoursesPlugin::SALE_STATUS_PENDING);
$template->assign('sale_status_completed', BuyCoursesPlugin::SALE_STATUS_COMPLETED);
$template->assign('invoicing_enable', $invoicingEnable);

$content = $template->fetch('buycourses/view/subscription_sales_report.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
