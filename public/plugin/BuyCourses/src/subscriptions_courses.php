<?php

declare(strict_types=1);

/* For license terms, see /license.txt */
/*
 * Configuration script for the Buy Courses plugin subscriptions.
 */

use Doctrine\ORM\Tools\Pagination\Paginator;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSession = 'true' === $plugin->get('include_sessions');
$taxEnable = 'true' === $plugin->get('tax_enable');

api_protect_admin_script(true);

Display::addFlash(
    Display::return_message(
        get_lang('Info').' - '.$plugin->get_lang('CoursesInSessionsDoesntDisplayHere'),
        'info'
    )
);

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$type = isset($_GET['type']) ? (int) $_GET['type'] : BuyCoursesPlugin::PRODUCT_TYPE_COURSE;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$first = $pageSize * ($currentPage - 1);

$qb = $plugin->getCourses($first, $pageSize);
$query = $qb->getQuery();
$courses = new Paginator($query, $fetchJoinCollection = true);

foreach ($courses as $course) {
    $item = $plugin->getSubscriptionItemByProduct($course->getId(), BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
    $course->buyCourseData = [];

    if (false !== $item) {
        $course->buyCourseData = $item;
    }
}

$totalItems = count($courses);
$pagesCount = $totalItems > 0 ? (int) ceil($totalItems / $pageSize) : 1;

// Breadcrumbs.
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php',
    'name' => $plugin->get_lang('plugin_title'),
];

$templateName = $plugin->get_lang('AvailableCourses');

$defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $defaultBackUrl;

$tpl = new Template($templateName);

$tpl->assign('page_title', $templateName);
$tpl->assign('plugin_title', $plugin->get_lang('plugin_title'));
$tpl->assign('back_url', $backUrl);
$tpl->assign(
    'frequency_url',
    api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/configure_frequency.php'
);

$tpl->assign('product_type_course', BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
$tpl->assign('product_type_session', BuyCoursesPlugin::PRODUCT_TYPE_SESSION);

$tpl->assign('courses', $courses);
$tpl->assign('sessions', []);

$tpl->assign('course_current_page', $currentPage);
$tpl->assign('course_pages_count', $pagesCount);
$tpl->assign('course_total_items', (int) $totalItems);

$tpl->assign('session_current_page', 1);
$tpl->assign('session_pages_count', 1);
$tpl->assign('session_total_items', 0);

$tpl->assign('sessions_are_included', $includeSession);
$tpl->assign('tax_enable', $taxEnable);

if ($taxEnable) {
    $globalParameters = $plugin->getGlobalParameters();

    $tpl->assign('global_tax_perc', $globalParameters['global_tax_perc']);
    $tpl->assign('tax_applies_to', $globalParameters['tax_applies_to']);
    $tpl->assign('tax_name', $globalParameters['tax_name']);
}

$content = $tpl->fetch('BuyCourses/view/subscriptions.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
