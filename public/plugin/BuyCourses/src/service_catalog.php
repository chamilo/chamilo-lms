<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Public catalog of services on sale.
 */

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeServices = 'true' === $plugin->get('include_services');

if (!$includeServices) {
    api_not_allowed(true);
}

$paymentStatus = isset($_GET['payment_status']) ? trim((string) $_GET['payment_status']) : '';
$paymentReason = isset($_GET['payment_reason']) ? trim((string) $_GET['payment_reason']) : '';
$serviceName = isset($_GET['service_name']) ? Security::remove_XSS((string) $_GET['service_name']) : '';

$getPendingReasonMessage = static function (BuyCoursesPlugin $plugin, string $pendingReason): string {
    switch ($pendingReason) {
        case 'address':
            return $plugin->get_lang('PendingReasonByAddress');
        case 'authorization':
            return $plugin->get_lang('PendingReasonByAuthorization');
        case 'echeck':
            return $plugin->get_lang('PendingReasonByEcheck');
        case 'intl':
            return $plugin->get_lang('PendingReasonByIntl');
        case 'multicurrency':
            return $plugin->get_lang('PendingReasonByMulticurrency');
        case 'order':
            return $plugin->get_lang('PendingReasonByOrder');
        case 'paymentreview':
            return $plugin->get_lang('PendingReasonByPaymentReview');
        case 'regulatoryreview':
            return $plugin->get_lang('PendingReasonByRegulatoryReview');
        case 'unilateral':
            return $plugin->get_lang('PendingReasonByUnilateral');
        case 'upgrade':
            return $plugin->get_lang('PendingReasonByUpgrade');
        case 'verify':
            return $plugin->get_lang('PendingReasonByVerify');
        case 'other':
        default:
            return $plugin->get_lang('PendingReasonByOther');
    }
};

switch ($paymentStatus) {
    case 'completed':
        if ('' !== $serviceName) {
            Display::addFlash(
                Display::return_message(
                    sprintf(
                        $plugin->get_lang('SubscriptionToServiceXSuccessful'),
                        $serviceName
                    ),
                    'success',
                    false
                )
            );
        } else {
            Display::addFlash(
                Display::return_message(
                    'Service purchase completed successfully.',
                    'success',
                    false
                )
            );
        }
        break;

    case 'cancelled':
        Display::addFlash(
            Display::return_message(
                $plugin->get_lang('OrderCancelled'),
                'warning',
                false
            )
        );
        break;

    case 'pending':
        Display::addFlash(
            Display::return_message(
                'PayPal payment is pending: '.$getPendingReasonMessage($plugin, $paymentReason),
                'warning',
                false
            )
        );
        break;

    case 'error':
        switch ($paymentReason) {
            case 'paypal_credentials':
                $message = $plugin->get_lang('PayPalCredentialsIncomplete');
                break;
            case 'paypal_ack':
                $message = $plugin->get_lang('PayPalPaymentNotConfirmed');
                break;
            case 'complete_service_sale':
                $message = $plugin->get_lang('ErrorContactPlatformAdmin');
                break;
            case 'unexpected_status':
            default:
                $message = $plugin->get_lang('PayPalUnexpectedStatus');
                break;
        }

        Display::addFlash(
            Display::return_message(
                $message,
                'error',
                false
            )
        );
        break;
}

/**
 * Normalize a price filter coming from the query string.
 */
$normalizePriceFilter = static function ($value, bool $roundUp = false): int {
    if (null === $value || '' === $value) {
        return 0;
    }

    $normalized = str_replace(',', '.', trim((string) $value));

    if ('' === $normalized || !is_numeric($normalized)) {
        return 0;
    }

    $amount = (float) $normalized;

    if ($roundUp) {
        return max(0, (int) ceil($amount));
    }

    return max(0, (int) floor($amount));
};

$nameFilter = isset($_GET['name']) ? trim((string) $_GET['name']) : '';
$minFilterValue = isset($_GET['min']) ? trim((string) $_GET['min']) : '';
$maxFilterValue = isset($_GET['max']) ? trim((string) $_GET['max']) : '';
$appliesToFilter = isset($_GET['applies_to'])
    ? (string) $_GET['applies_to']
    : (string) BuyCoursesPlugin::SERVICE_TYPE_USER;

$allowedAppliesToValues = [
    (string) BuyCoursesPlugin::SERVICE_TYPE_USER,
];
if (!in_array($appliesToFilter, $allowedAppliesToValues, true)) {
    $appliesToFilter = (string) BuyCoursesPlugin::SERVICE_TYPE_USER;
}

$minFilter = $normalizePriceFilter($minFilterValue, false);
$maxFilter = $normalizePriceFilter($maxFilterValue, true);

if ($minFilter > 0 && $maxFilter > 0 && $minFilter > $maxFilter) {
    [$minFilter, $maxFilter] = [$maxFilter, $minFilter];
    [$minFilterValue, $maxFilterValue] = [$maxFilterValue, $minFilterValue];
}

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$first = $pageSize * ($currentPage - 1);

$serviceList = $plugin->getCatalogServiceList(
    $first,
    $pageSize,
    '' !== $nameFilter ? $nameFilter : null,
    $minFilter,
    $maxFilter,
    $appliesToFilter
);

foreach ($serviceList as &$service) {
    $service['has_blocking_sale'] = $plugin->hasBlockingUserServiceSaleForCurrentBuyer((int) $service['id']);
}
unset($service);

$totalItems = (int) $plugin->getCatalogServiceList(
    0,
    $pageSize,
    '' !== $nameFilter ? $nameFilter : null,
    $minFilter,
    $maxFilter,
    $appliesToFilter,
    'count'
);

try {
    $selectedCurrency = $plugin->getSelectedCurrency();
} catch (Exception) {
    $selectedCurrency = [];
}

$selectedCurrencyIsoCode = (string) ($selectedCurrency['iso_code'] ?? '');

foreach ($serviceList as &$service) {
    $isoCode = (string) ($service['iso_code'] ?? $selectedCurrencyIsoCode);
    $priceValue = (float) ($service['total_price'] ?? 0);

    if (!empty($service['total_price_formatted'])) {
        $service['display_price'] = (string) $service['total_price_formatted'];
    } elseif ('' !== $isoCode) {
        $service['display_price'] = $plugin->getPriceWithCurrencyFromIsoCode($priceValue, $isoCode);
    } else {
        $service['display_price'] = number_format($priceValue, 2, '.', ',');
    }
}
unset($service);

$canBuyServices = api_is_platform_admin() || api_is_allowed_to_create_course();
$buyerRoleNotice = null;

if (!$canBuyServices) {
    $buyerRoleNotice = $plugin->get_lang('ServicesOnlyForTeachers');
}

$pagesCount = $totalItems > 0 ? (int) ceil($totalItems / $pageSize) : 1;

$pluginIndexUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $pluginIndexUrl;

if (api_is_platform_admin()) {
    $interbreadcrumb[] = [
        'name' => get_lang('Administration'),
        'url' => api_get_path(WEB_PATH).'admin',
    ];
    $interbreadcrumb[] = [
        'name' => get_lang('Plugins'),
        'url' => api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins',
    ];
    $interbreadcrumb[] = [
        'url' => $pluginIndexUrl,
        'name' => $plugin->get_lang('plugin_title'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => $pluginIndexUrl,
        'name' => get_lang('TabsDashboard'),
    ];
}

$templateName = $plugin->get_lang('ListOfServicesOnSale');

$tpl = new Template($templateName);

$tpl->assign('page_title', $templateName);
$tpl->assign('back_url', $backUrl);

$tpl->assign('showing_courses', false);
$tpl->assign('showing_sessions', false);
$tpl->assign('showing_services', true);

$tpl->assign('show_courses_tab', false);
$tpl->assign('show_sessions_tab', false);
$tpl->assign('show_services_tab', true);

$tpl->assign('courses', []);
$tpl->assign('sessions', []);
$tpl->assign('services', $serviceList);

$tpl->assign('sessions_are_included', false);
$tpl->assign('services_are_included', $includeServices);

$tpl->assign('name_filter_value', $nameFilter);
$tpl->assign('min_filter_value', $minFilterValue);
$tpl->assign('max_filter_value', $maxFilterValue);
$tpl->assign('applies_to_filter_value', $appliesToFilter);

$tpl->assign('pagination_current_page', $currentPage);
$tpl->assign('pagination_pages_count', $pagesCount);
$tpl->assign('pagination_total_items', $totalItems);
$tpl->assign('pagination_base_path', 'service_catalog.php');

$tpl->assign('can_buy_services', $canBuyServices);
$tpl->assign('buyer_role_notice', $buyerRoleNotice);

$content = $tpl->fetch('BuyCourses/view/catalog.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
