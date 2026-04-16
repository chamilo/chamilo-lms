<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/**
 * List of sessions.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSessions = 'true' === $plugin->get('include_sessions');
$includeServices = 'true' === $plugin->get('include_services');

if (!$includeSessions) {
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
$sessionCategory = isset($_GET['session_category']) ? max(0, (int) $_GET['session_category']) : 0;

$minFilter = $normalizePriceFilter($minFilterValue, false);
$maxFilter = $normalizePriceFilter($maxFilterValue, true);

if ($minFilter > 0 && $maxFilter > 0 && $minFilter > $maxFilter) {
    [$minFilter, $maxFilter] = [$maxFilter, $minFilter];
    [$minFilterValue, $maxFilterValue] = [$maxFilterValue, $minFilterValue];
}

$categoriesList = SessionManager::get_all_session_category();
if (false === $categoriesList) {
    $categoriesList = [];
}

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$first = $pageSize * ($currentPage - 1);

$sessionList = $plugin->getCatalogSessionList(
    $first,
    $pageSize,
    '' !== $nameFilter ? $nameFilter : null,
    $minFilter,
    $maxFilter,
    'all',
    $sessionCategory
);

$totalItems = (int) $plugin->getCatalogSessionList(
    0,
    $pageSize,
    '' !== $nameFilter ? $nameFilter : null,
    $minFilter,
    $maxFilter,
    'count',
    $sessionCategory
);

$pagesCount = $totalItems > 0 ? (int) ceil($totalItems / $pageSize) : 1;

$pluginIndexUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $pluginIndexUrl;

$templateName = $plugin->get_lang('CourseListOnSale');
$tpl = new Template($templateName);

$tpl->assign('page_title', $templateName);
$tpl->assign('back_url', $backUrl);

$tpl->assign('showing_courses', false);
$tpl->assign('showing_sessions', true);
$tpl->assign('showing_services', false);

$tpl->assign('show_courses_tab', true);
$tpl->assign('show_sessions_tab', $includeSessions);
$tpl->assign('show_services_tab', false);

$tpl->assign('courses', []);
$tpl->assign('sessions', $sessionList);
$tpl->assign('services', []);

$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('services_are_included', $includeServices);

$tpl->assign('name_filter_value', $nameFilter);
$tpl->assign('min_filter_value', $minFilterValue);
$tpl->assign('max_filter_value', $maxFilterValue);
$tpl->assign('session_category_value', $sessionCategory);
$tpl->assign('session_categories', $categoriesList);

$tpl->assign('pagination_current_page', $currentPage);
$tpl->assign('pagination_pages_count', $pagesCount);
$tpl->assign('pagination_total_items', $totalItems);
$tpl->assign('pagination_base_path', 'session_catalog.php');

$content = $tpl->fetch('BuyCourses/view/catalog.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
