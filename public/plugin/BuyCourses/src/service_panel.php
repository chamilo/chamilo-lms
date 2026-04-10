<?php

declare(strict_types=1);

/**
 * User Panel.
 */
$cidReset = true;

require_once '../../../main/inc/global.inc.php';
$plugin = BuyCoursesPlugin::create();
$includeServices = 'true' === $plugin->get('include_services');
$includeSessions = 'true' === $plugin->get('include_sessions');
$servicesOnly = 'true' === $plugin->get('show_services_only');

$userInfo = api_get_user_info();

if (!$userInfo) {
    api_not_allowed(true);
}
$serviceTypes = $plugin->getServiceTypes();
$serviceSaleStatuses['status_cancelled'] = BuyCoursesPlugin::SERVICE_STATUS_CANCELLED;
$serviceSaleStatuses['status_pending'] = BuyCoursesPlugin::SERVICE_STATUS_PENDING;
$serviceSaleStatuses['status_completed'] = BuyCoursesPlugin::SERVICE_STATUS_COMPLETED;

$activeServices = [];
foreach ($plugin->getActiveServicesForUser($userInfo['user_id']) as $sale) {
    $activeServices[] = [
        'id' => $sale['id'],
        'name' => $sale['service']['name'],
        'service_type' => $serviceTypes[$sale['service']['applies_to']] ?? get_lang('None'),
        'reference' => $sale['reference'],
        'date_start' => api_format_date(api_get_local_time($sale['date_start']), DATE_TIME_FORMAT_LONG_24H),
        'date_end' => api_format_date(api_get_local_time($sale['date_end']), DATE_TIME_FORMAT_LONG_24H),
        'image' => $sale['service']['image'] ?: Template::get_icon_path('session_default.png'),
        'benefit_summaries' => array_values(array_filter(array_map(static function (array $benefit): ?string {
            return $benefit['active_summary'] ?? null;
        }, $sale['benefit_summaries'] ?? []))),
        'receipt_url' => !empty($sale['invoice']) ? $plugin->getInvoiceUrl((int) $sale['id'], 1) : null,
    ];
}

$purchaseHistory = [];
foreach ($plugin->getPurchaseHistoryForUser($userInfo['user_id']) as $purchase) {
    $purchaseHistory[] = [
        'date' => !empty($purchase['date']) ? api_format_date(api_get_local_time($purchase['date']), DATE_TIME_FORMAT_LONG_24H) : '',
        'type' => $purchase['type'],
        'product_name' => $purchase['product_name'],
        'reference' => $purchase['reference'],
        'amount' => $purchase['amount'],
        'status' => $purchase['status'],
        'receipt_url' => $purchase['receipt_url'],
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
$htmlHeadXtra[] = api_get_js_simple($webPluginPath.'BuyCourses/resources/js/modals.js');

$templateName = $plugin->get_lang('TabsDashboard');
$tpl = new Template($templateName);
$tpl->assign('showing_courses', true);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('service_sale_statuses', $serviceSaleStatuses);
$tpl->assign('active_service_list', $activeServices);
$tpl->assign('purchase_history', $purchaseHistory);
if ($servicesOnly) {
    $tpl->assign('show_services_only', true);
}

$content = $tpl->fetch('BuyCourses/view/service_panel.tpl');

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);
$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
