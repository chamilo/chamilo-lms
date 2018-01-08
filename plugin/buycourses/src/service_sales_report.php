<?php
/* For license terms, see /license.txt */

/**
 * List of pending payments of the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */

$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

$paypalEnable = $plugin->get('paypal_enable');
$commissionsEnable = $plugin->get('commissions_enable');
$includeServices = $plugin->get('include_services');

$saleStatuses = $plugin->getServiceSaleStatuses();
$paymentTypes = $plugin->getPaymentTypes();

$form = new FormValidator('search', 'get');

$form->addSelect('status', $plugin->get_lang('OrderStatus'), $saleStatuses, ['cols-size' => [0, 0, 0]]);
$form->addText('user', get_lang('User'), false, ['cols-size' => [0, 0, 0]]);
$form->addButtonSearch(get_lang('Search'), 'search');

$servicesSales = $plugin->getServiceSale();
$serviceSaleList = [];

foreach ($servicesSales as $sale) {
    $serviceSaleList[] = [
        'id' => $sale['id'],
        'reference' => $sale['reference'],
        'status' => $sale['status'],
        'date' => api_format_date($sale['buy_date'], DATE_TIME_FORMAT_LONG_24H),
        'currency' => $sale['currency'],
        'price' => $sale['price'],
        'service_type' => $sale['service']['applies_to'],
        'service_name' => $sale['service']['name'],
        'complete_user_name' => $sale['buyer']['name']
    ];
}

//View
$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('plugin_title')];

$templateName = $plugin->get_lang('SalesReport');

$template = new Template($templateName);

if ($paypalEnable == 'true' && $commissionsEnable == 'true') {
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
$template->assign('sale_list', $serviceSaleList);
$template->assign('sale_status_cancelled', BuyCoursesPlugin::SERVICE_STATUS_CANCELLED);
$template->assign('sale_status_pending', BuyCoursesPlugin::SERVICE_STATUS_PENDING);
$template->assign('sale_status_completed', BuyCoursesPlugin::SERVICE_STATUS_COMPLETED);
$content = $template->fetch('buycourses/view/service_sales_report.tpl');
$template->assign('content', $content);
$template->display_one_col_template();
