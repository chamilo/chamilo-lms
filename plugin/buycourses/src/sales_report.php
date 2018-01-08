<?php
/* For license terms, see /license.txt */

/**
 * List of pending payments of the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
//Initialization
$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

$paypalEnable = $plugin->get('paypal_enable');
$commissionsEnable = $plugin->get('commissions_enable');
$includeServices = $plugin->get('include_services');

if (isset($_GET['order'])) {
    $sale = $plugin->getSale($_GET['order']);

    if (empty($sale)) {
        api_not_allowed(true);
    }

    $urlToRedirect = api_get_self().'?';

    switch ($_GET['action']) {
        case 'confirm':
            $plugin->completeSale($sale['id']);
            $plugin->storePayouts($sale['id']);
            Display::addFlash(
                Display::return_message(
                    sprintf($plugin->get_lang('SubscriptionToCourseXSuccessful'), $sale['product_name']),
                    'success'
                )
            );

            $urlToRedirect .= http_build_query([
                'status' => BuyCoursesPlugin::SALE_STATUS_COMPLETED,
                'sale' => $sale['id']
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
                'sale' => $sale['id']
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
$searchTerm = '';

$form = new FormValidator('search', 'get');

if ($form->validate()) {
    $selectedFilterType = $form->getSubmitValue('filter_type');
    $selectedStatus = $form->getSubmitValue('status');
    $searchTerm = $form->getSubmitValue('user');

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
    [$plugin->get_lang('ByStatus'), $plugin->get_lang('ByUser')]
);
$form->addHtml('<div id="report-by-status" '.($selectedFilterType !== '0' ? 'style="display:none"' : '').'>');
$form->addSelect('status', $plugin->get_lang('OrderStatus'), $saleStatuses);
$form->addHtml('</div>');
$form->addHtml('<div id="report-by-user" '.($selectedFilterType !== '1' ? 'style="display:none"' : '').'>');
$form->addText('user', get_lang('UserName'), false);
$form->addHtml('</div>');
$form->addButtonFilter(get_lang('Search'));
$form->setDefaults([
    'filter_type' => $selectedFilterType,
    'status' => $selectedStatus
]);

switch ($selectedFilterType) {
    case '0':
        $sales = $plugin->getSaleListByStatus($selectedStatus);
        break;
    case '1':
        $sales = $plugin->getSaleListByUser($searchTerm);
        break;
}

$saleList = [];

foreach ($sales as $sale) {
    $saleList[] = [
        'id' => $sale['id'],
        'reference' => $sale['reference'],
        'status' => $sale['status'],
        'date' => api_format_date($sale['date'], DATE_TIME_FORMAT_LONG_24H),
        'currency' => $sale['iso_code'],
        'price' => $sale['price'],
        'product_name' => $sale['product_name'],
        'product_type' => $productTypes[$sale['product_type']],
        'complete_user_name' => api_get_person_name($sale['firstname'], $sale['lastname']),
        'payment_type' => $paymentTypes[$sale['payment_type']]
    ];
}

//View
$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('plugin_title')];

$templateName = $plugin->get_lang('SalesReport');

$template = new Template($templateName);

if ($paypalEnable == "true" && $commissionsEnable == "true") {
    $toolbar = Display::toolbarButton(
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

if ($commissionsEnable == "true") {
    $toolbar = Display::toolbarButton(
        $plugin->get_lang('PayoutReport'),
        api_get_path(WEB_PLUGIN_PATH).'buycourses/src/payout_report.php',
        'money',
        'info',
        ['title' => $plugin->get_lang('PayoutReport')]
    );

    $template->assign(
        'actions',
        Display::toolbarAction('toolbar', [$toolbar])
    );
}
$template->assign('form', $form->returnForm());
$template->assign('selected_sale', $selectedSale);
$template->assign('selected_status', $selectedStatus);
$template->assign('services_are_included', $includeServices);
$template->assign('sale_list', $saleList);
$template->assign('sale_status_canceled', BuyCoursesPlugin::SALE_STATUS_CANCELED);
$template->assign('sale_status_pending', BuyCoursesPlugin::SALE_STATUS_PENDING);
$template->assign('sale_status_completed', BuyCoursesPlugin::SALE_STATUS_COMPLETED);

$content = $template->fetch('buycourses/view/sales_report.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
