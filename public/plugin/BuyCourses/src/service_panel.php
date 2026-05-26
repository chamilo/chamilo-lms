<?php

declare(strict_types=1);

/**
 * User service panel.
 */
$cidReset = true;

require_once '../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();

$includeServices = 'true' === $plugin->get('include_services');
$includeSessions = 'true' === $plugin->get('include_sessions');
$servicesOnly = 'true' === $plugin->get('show_services_only');

$userInfo = api_get_user_info();

if (!$userInfo || !$includeServices) {
    api_not_allowed(true);
}

$serviceTypes = $plugin->getServiceTypes();
$webPluginPath = api_get_path(WEB_PLUGIN_PATH);

$activeServices = [];

foreach ($plugin->getActiveServicesForUser((int) $userInfo['user_id']) as $sale) {
    $service = $sale['service'] ?? [];
    $saleId = (int) ($sale['id'] ?? 0);
    $serviceId = (int) ($service['id'] ?? 0);
    $paymentType = (int) ($sale['payment_type'] ?? 0);
    $recurringPayment = (int) ($sale['recurring_payment'] ?? BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_DISABLED);
    $isRenewable = 1 === (int) ($service['renewable'] ?? 0);
    $isPayPalPayment = BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL === $paymentType;

    $canEnableRecurring = $isRenewable
        && $isPayPalPayment
        && BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED !== $recurringPayment;

    $canCancelRecurring = $isRenewable
        && $isPayPalPayment
        && BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED === $recurringPayment
        && !empty($sale['recurring_profile_id']);

    $recurringStatusLabel = match ($recurringPayment) {
        BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED => 'Auto billing enabled',
        BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_SUSPENDED => 'Auto billing suspended',
        BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_CANCELLED => 'Auto billing cancelled',
        default => 'Auto billing disabled',
    };

    $benefitSummaries = array_values(array_filter(array_map(static function (array $benefit): ?string {
        return $benefit['active_summary'] ?? null;
    }, $sale['benefit_summaries'] ?? [])));

    $activeServices[] = [
        'id' => $saleId,
        'service_id' => $serviceId,
        'name' => (string) ($service['name'] ?? ''),
        'description' => (string) ($service['description'] ?? ''),
        'service_type' => $serviceTypes[(int) ($service['applies_to'] ?? 0)] ?? get_lang('None'),
        'reference' => (string) ($sale['reference'] ?? ''),
        'date_start' => !empty($sale['date_start'])
            ? api_format_date(api_get_local_time($sale['date_start']), DATE_TIME_FORMAT_LONG_24H)
            : '',
        'date_end' => !empty($sale['date_end'])
            ? api_format_date(api_get_local_time($sale['date_end']), DATE_TIME_FORMAT_LONG_24H)
            : '',
        'image' => !empty($service['image']) ? (string) $service['image'] : Template::get_icon_path('session_default.png'),
        'benefit_summaries' => $benefitSummaries,
        'receipt_url' => !empty($sale['invoice']) ? $plugin->getInvoiceUrl($saleId, 1) : null,
        'info_url' => $webPluginPath.'BuyCourses/src/service_information.php?service_id='.$serviceId.'&sale_id='.$saleId,
        'is_renewable' => $isRenewable,
        'recurring_status' => $recurringPayment,
        'recurring_status_label' => $recurringStatusLabel,
        'next_charge_date' => !empty($sale['next_charge_date'])
            ? api_format_date(api_get_local_time($sale['next_charge_date']), DATE_TIME_FORMAT_LONG_24H)
            : '',
        'recurring_profile_id' => (string) ($sale['recurring_profile_id'] ?? ''),
        'can_enable_recurring' => $canEnableRecurring,
        'can_cancel_recurring' => $canCancelRecurring,
        'enable_recurring_url' => $webPluginPath.'BuyCourses/src/recurring_payment_process.php?action=enable_recurring_payment&order='.$saleId,
        'cancel_recurring_url' => $webPluginPath.'BuyCourses/src/recurring_payment_process.php?action=cancel_recurring_payment&order='.$saleId,
    ];
}

$purchaseHistory = [];

foreach ($plugin->getPurchaseHistoryForUser((int) $userInfo['user_id']) as $purchase) {
    $purchaseHistory[] = [
        'date' => !empty($purchase['date'])
            ? api_format_date(api_get_local_time($purchase['date']), DATE_TIME_FORMAT_LONG_24H)
            : '',
        'type' => (string) ($purchase['type'] ?? ''),
        'product_name' => (string) ($purchase['product_name'] ?? ''),
        'reference' => (string) ($purchase['reference'] ?? ''),
        'amount' => (string) ($purchase['amount'] ?? ''),
        'status' => (int) ($purchase['status'] ?? 0),
        'receipt_url' => $purchase['receipt_url'] ?? null,
    ];
}

$templateName = $plugin->get_lang('MyServices');
$tpl = new Template($templateName);

$tpl->assign('services_are_included', $includeServices);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('show_services_only', $servicesOnly);
$tpl->assign('active_service_list', $activeServices);
$tpl->assign('purchase_history', $purchaseHistory);

$content = $tpl->fetch('BuyCourses/view/service_panel.tpl');

$tpl->assign('actions', '');
$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
