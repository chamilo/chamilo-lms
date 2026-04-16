<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Public catalog of subscriptions on sale for courses.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSessions = 'true' === $plugin->get('include_sessions');

$nameFilter = isset($_GET['name']) ? trim((string) $_GET['name']) : '';

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$first = $pageSize * ($currentPage - 1);

$courseList = $plugin->getCatalogSubscriptionCourseList(
    $first,
    $pageSize,
    $nameFilter
);

$totalItems = $plugin->getCatalogSubscriptionCourseList(
    0,
    $pageSize,
    $nameFilter,
    'count'
);

$pagesCount = $totalItems > 0 ? (int) ceil($totalItems / $pageSize) : 1;

// Keep a safe back target.
$defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $defaultBackUrl;

// View.
if (api_is_platform_admin()) {
    $interbreadcrumb[] = [
        'url' => 'subscriptions_courses.php',
        'name' => $plugin->get_lang('AvailableCoursesConfiguration'),
    ];
    $interbreadcrumb[] = [
        'url' => 'paymentsetup.php',
        'name' => $plugin->get_lang('PaymentsConfiguration'),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => 'course_panel.php',
        'name' => get_lang('TabsDashboard'),
    ];
}

$templateName = $plugin->get_lang('SubscriptionListOnSale');
$tpl = new Template($templateName);

$tpl->assign('page_title', $templateName);
$tpl->assign('back_url', $backUrl);

$tpl->assign('showing_courses', true);
$tpl->assign('showing_sessions', false);

$tpl->assign('courses', $courseList);
$tpl->assign('sessions', []);

$tpl->assign('sessions_are_included', $includeSessions);

$tpl->assign('coursesExist', true);

/*
 * Do not query subscription sessions here just to decide whether the tab
 * should be visible. This page is the course subscription catalog and the
 * extra query may fail depending on the active filter. The plugin setting
 * is enough to decide whether the Sessions tab is available.
 */
$tpl->assign('sessionExist', $includeSessions);

$tpl->assign('name_filter_value', $nameFilter);

$tpl->assign('pagination_current_page', $currentPage);
$tpl->assign('pagination_pages_count', $pagesCount);
$tpl->assign('pagination_total_items', (int) $totalItems);
$tpl->assign('pagination_base_path', 'subscription_course_catalog.php');

$content = $tpl->fetch('BuyCourses/view/subscription_catalog.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
