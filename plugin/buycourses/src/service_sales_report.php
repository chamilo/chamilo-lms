<?php
/* For license terms, see /license.txt */

/**
 * List of pending payments of the Buy Courses plugin.
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

$saleStatuses = $plugin->getServiceSaleStatuses();
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : BuyCoursesPlugin::SALE_STATUS_PENDING;
$form = new FormValidator('search', 'get');

if ($form->validate()) {
    $selectedStatus = $form->getSubmitValue('status');
    if ($selectedStatus === false) {
        $selectedStatus = BuyCoursesPlugin::SALE_STATUS_PENDING;
    }
}

$form->addSelect('status', $plugin->get_lang('OrderStatus'), $saleStatuses, ['cols-size' => [0, 0, 0]]);
$form->addText('user', get_lang('User'), false, ['cols-size' => [0, 0, 0]]);
$form->addButtonSearch(get_lang('Search'), 'search');

$servicesSales = $plugin->getServiceSales(0, $selectedStatus);

foreach ($servicesSales as &$sale) {
    if (isset($sale['discount_amount']) && $sale['discount_amount'] != 0) {
        $sale['total_discount'] = $plugin->getPriceWithCurrencyFromIsoCode($sale['discount_amount'], $sale['iso_code']);
        $sale['coupon_code'] = $plugin->getServiceSaleCouponCode($sale['id']);
    }
}

$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('plugin_title')];

$webPluginPath = api_get_path(WEB_PLUGIN_PATH);
$htmlHeadXtra[] = api_get_css($webPluginPath.'buycourses/resources/css/style.css');
$htmlHeadXtra[] = api_get_css($webPluginPath.'buycourses/resources/js/modals.js');

$templateName = $plugin->get_lang('SalesReport');

$template = new Template($templateName);

$toolbar = Display::url(
    Display::returnFontAwesomeIcon('file-excel-o').
    get_lang('GenerateReport'),
    api_get_path(WEB_PLUGIN_PATH).'buycourses/src/export_report.php',
    ['class' => 'btn btn-primary']
);

if ($paypalEnable == 'true' && $commissionsEnable == 'true') {
    $toolbar .= Display::toolbarButton(
        $plugin->get_lang('PaypalPayoutCommissions'),
        api_get_path(WEB_PLUGIN_PATH).'buycourses/src/paypal_payout.php',
        'paypal',
        'primary',
        ['title' => $plugin->get_lang('PaypalPayoutCommissions')]
    );
}

$template->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);

if ($commissionsEnable == 'true') {
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
$template->assign('showing_services', true);
$template->assign('services_are_included', $includeServices);
$template->assign('sale_list', $servicesSales);
$template->assign('sale_status_cancelled', BuyCoursesPlugin::SERVICE_STATUS_CANCELLED);
$template->assign('sale_status_pending', BuyCoursesPlugin::SERVICE_STATUS_PENDING);
$template->assign('sale_status_completed', BuyCoursesPlugin::SERVICE_STATUS_COMPLETED);
$template->assign('invoicing_enable', $invoicingEnable);
$content = $template->fetch('buycourses/view/service_sales_report.tpl');
$template->assign('content', $content);
$template->assign('header', $templateName);
$template->display_one_col_template();
