<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * List of services.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSessions = 'true' === $plugin->get('include_sessions');
$includeServices = 'true' === $plugin->get('include_services');

if (!$includeServices) {
    api_not_allowed(true);
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
$appliesToFilter = isset($_GET['applies_to']) ? (string) $_GET['applies_to'] : '';

$allowedAppliesToValues = ['', '0', '1', '2', '3', '4'];
if (!in_array($appliesToFilter, $allowedAppliesToValues, true)) {
    $appliesToFilter = '';
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

$totalItems = (int) $plugin->getCatalogServiceList(
    0,
    $pageSize,
    '' !== $nameFilter ? $nameFilter : null,
    $minFilter,
    $maxFilter,
    $appliesToFilter,
    'count'
);

$pagesCount = $totalItems > 0 ? (int) ceil($totalItems / $pageSize) : 1;

$pluginIndexUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $pluginIndexUrl;

if (api_is_platform_admin()) {
    $backUrl = 'paymentsetup.php';

    $interbreadcrumb[] = [
        'name' => get_lang('Administration'),
        'url' => api_get_path(WEB_PATH).'admin',
    ];
    $interbreadcrumb[] = [
        'name' => get_lang('Plugins'),
        'url' => api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Plugins',
    ];
    $interbreadcrumb[] = [
        'url' => 'list.php',
        'name' => $plugin->get_lang('AvailableCoursesConfiguration'),
    ];
    $interbreadcrumb[] = [
        'url' => 'paymentsetup.php',
        'name' => $plugin->get_lang('PaymentsConfiguration'),
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

$tpl->assign('show_courses_tab', true);
$tpl->assign('show_sessions_tab', $includeSessions);
$tpl->assign('show_services_tab', $includeServices);

$tpl->assign('courses', []);
$tpl->assign('sessions', []);
$tpl->assign('services', $serviceList);

$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('services_are_included', $includeServices);

$tpl->assign('name_filter_value', $nameFilter);
$tpl->assign('min_filter_value', $minFilterValue);
$tpl->assign('max_filter_value', $maxFilterValue);
$tpl->assign('applies_to_filter_value', $appliesToFilter);

$tpl->assign('pagination_current_page', $currentPage);
$tpl->assign('pagination_pages_count', $pagesCount);
$tpl->assign('pagination_total_items', $totalItems);
$tpl->assign('pagination_base_path', 'service_catalog.php');

$content = $tpl->fetch('BuyCourses/view/catalog.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
