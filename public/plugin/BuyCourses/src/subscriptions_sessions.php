<?php

declare(strict_types=1);

/* For license terms, see /license.txt */
/*
 * Configuration script for the Buy Courses plugin subscriptions sessions list.
 */

use Doctrine\ORM\Tools\Pagination\Paginator;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSession = 'true' === $plugin->get('include_sessions');

if (!$includeSession) {
    api_not_allowed(true);
}

$includeServices = 'true' === $plugin->get('include_services');
$taxEnable = 'true' === $plugin->get('tax_enable');

api_protect_admin_script(true);

Display::addFlash(
    Display::return_message(
        get_lang('Info').' - '.$plugin->get_lang('CoursesInSessionsDoesntDisplayHere'),
        'info'
    )
);

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$first = $pageSize * ($currentPage - 1);

// Breadcrumbs.
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php',
    'name' => $plugin->get_lang('plugin_title'),
];

$templateName = $plugin->get_lang('AvailableCourses');
$tpl = new Template($templateName);

$query = CoursesAndSessionsCatalog::browseSessions(
    null,
    ['start' => $first, 'length' => $pageSize],
    true
);

$sessions = new Paginator($query, $fetchJoinCollection = true);

foreach ($sessions as $session) {
    $item = $plugin->getSubscriptionItemByProduct(
        $session->getId(),
        BuyCoursesPlugin::PRODUCT_TYPE_SESSION
    );

    $session->buyCourseData = [];

    if (false !== $item) {
        $session->buyCourseData = $item;
    }
}

$totalItems = count($sessions);
$pagesCount = $totalItems > 0 ? (int) ceil($totalItems / $pageSize) : 1;

$defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $defaultBackUrl;
$referer = $_SERVER['HTTP_REFERER'] ?? '';

if (is_string($referer) && '' !== $referer) {
    $allowedFragment = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/';

    if (false !== strpos($referer, $allowedFragment)) {
        $backUrl = $referer;
    }
}

$frequencyUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/configure_frequency.php';

$tpl->assign('page_title', $templateName);
$tpl->assign('plugin_title', $plugin->get_lang('plugin_title'));
$tpl->assign('back_url', $backUrl);
$tpl->assign('frequency_url', $frequencyUrl);

$tpl->assign('product_type_course', BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
$tpl->assign('product_type_session', BuyCoursesPlugin::PRODUCT_TYPE_SESSION);

$tpl->assign('sessions_are_included', $includeSession);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('tax_enable', $taxEnable);

$tpl->assign('courses', []);
$tpl->assign('sessions', $sessions);

$tpl->assign('course_total_items', 0);
$tpl->assign('course_current_page', 1);
$tpl->assign('course_pages_count', 1);

$tpl->assign('session_total_items', (int) $totalItems);
$tpl->assign('session_current_page', $currentPage);
$tpl->assign('session_pages_count', $pagesCount);

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
