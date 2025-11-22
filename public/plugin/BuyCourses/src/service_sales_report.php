<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/**
 * List of pending payments of the Buy Courses plugin.
 */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();
$httpRequest = Container::getRequest();

$paypalEnable = $plugin->get('paypal_enable');
$commissionsEnable = $plugin->get('commissions_enable');
$includeServices = $plugin->get('include_services');
$invoicingEnable = 'true' === $plugin->get('invoicing_enable');

$saleStatuses = $plugin->getServiceSaleStatuses();
$selectedStatus = $httpRequest->query->getInt('status', BuyCoursesPlugin::SALE_STATUS_PENDING);
$form = new FormValidator('search', 'get');

if ($form->validate()) {
    $selectedStatus = (int) $form->getSubmitValue('status');
    if (!$selectedStatus) {
        $selectedStatus = BuyCoursesPlugin::SALE_STATUS_PENDING;
    }
}

$form->addSelect('status', $plugin->get_lang('OrderStatus'), $saleStatuses, ['cols-size' => [0, 0, 0]]);
$form->addText('user', get_lang('User'), false, ['cols-size' => [0, 0, 0]]);
$form->addButtonSearch(get_lang('Search'), 'search');

$servicesSales = $plugin->getServiceSales(0, $selectedStatus);

foreach ($servicesSales as &$sale) {
    $sale['total_discount'] = 0;
    $sale['coupon_code'] = '';

    if (isset($sale['discount_amount']) && 0 != $sale['discount_amount']) {
        $sale['total_discount'] = $plugin->getPriceWithCurrencyFromIsoCode($sale['discount_amount'], $sale['iso_code']);
        $sale['coupon_code'] = $plugin->getServiceSaleCouponCode($sale['id']);
    }
}

$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('plugin_title')];

$webPluginPath = api_get_path(WEB_PLUGIN_PATH);
$htmlHeadXtra[] = api_get_css($webPluginPath.'BuyCourses/resources/css/style.css');
$htmlHeadXtra[] = api_get_css($webPluginPath.'BuyCourses/resources/js/modals.js');

$templateName = $plugin->get_lang('SalesReport');

$template = new Template($templateName);

$toolbar = Display::url(
    Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET).
    get_lang('GenerateReport'),
    api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/export_report.php',
    ['class' => 'btn btn-primary']
);

if ('true' == $paypalEnable && 'true' == $commissionsEnable) {
    $toolbar .= Display::toolbarButton(
        $plugin->get_lang('PaypalPayoutCommissions'),
        api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/paypal_payout.php',
        'paypal',
        'primary',
        ['title' => $plugin->get_lang('PaypalPayoutCommissions')]
    );
}

$template->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);

if ('true' == $commissionsEnable) {
    $toolbar = Display::toolbarButton(
        $plugin->get_lang('PayoutReport'),
        api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/payout_report.php',
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
$content = $template->fetch('BuyCourses/view/service_sales_report.tpl');
$template->assign('content', $content);
$template->assign('header', $templateName);
$template->display_one_col_template();
