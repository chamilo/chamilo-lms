<?php
/**
 * User Panel.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once '../../../main/inc/global.inc.php';
$plugin = BuyCoursesPlugin::create();
$includeServices = $plugin->get('include_services') === 'true';
$includeSessions = $plugin->get('include_sessions') === 'true';
$servicesOnly = $plugin->get('show_services_only') === 'true';

$userInfo = api_get_user_info();

if (!$userInfo) {
    api_not_allowed(true);
}
$serviceTypes = $plugin->getServiceTypes();
$serviceSaleStatuses['status_cancelled'] = BuyCoursesPlugin::SERVICE_STATUS_CANCELLED;
$serviceSaleStatuses['status_pending'] = BuyCoursesPlugin::SERVICE_STATUS_PENDING;
$serviceSaleStatuses['status_completed'] = BuyCoursesPlugin::SERVICE_STATUS_COMPLETED;

$serviceSales = $plugin->getServiceSales($userInfo['user_id']);
$saleList = [];

foreach ($serviceSales as $sale) {
    $saleList[] = [
        'id' => $sale['id'],
        'name' => $sale['service']['name'],
        'service_type' => $serviceTypes[$sale['service']['applies_to']],
        'applies_to' => $sale['service']['applies_to'],
        'reference' => $sale['reference'],
        'date' => api_format_date(api_get_local_time($sale['buy_date']), DATE_TIME_FORMAT_LONG_24H),
        'date_end' => api_format_date(api_get_local_time($sale['date_end']), DATE_TIME_FORMAT_LONG_24H),
        'currency' => $sale['currency'],
        'price' => $sale['price'],
        'status' => $sale['status'],
    ];
}

$toolbar = Display::toolbarButton(
    $plugin->get_lang('CourseListOnSale'),
    'course_catalog.php',
    'search-plus',
    'primary',
    ['title' => $plugin->get_lang('CourseListOnSale')]
);

$webPluginPath = api_get_path(WEB_PLUGIN_PATH);
$htmlHeadXtra[] = api_get_css($webPluginPath.'buycourses/resources/css/style.css');
$htmlHeadXtra[] = api_get_js_simple($webPluginPath.'buycourses/resources/js/modals.js');

$templateName = $plugin->get_lang('TabsDashboard');
$tpl = new Template($templateName);
$tpl->assign('showing_courses', true);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('service_sale_statuses', $serviceSaleStatuses);
$tpl->assign('sale_list', $saleList);
if ($servicesOnly) {
    $tpl->assign('show_services_only', true);
}

$content = $tpl->fetch('buycourses/view/service_panel.tpl');

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);
$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
