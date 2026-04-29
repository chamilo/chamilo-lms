<?php

declare(strict_types=1);

/* For license terms, see /license.txt */
/**
 * Configuration script for the Buy Courses plugin.
 */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$httpRequest = Container::getRequest();

$includeSession = 'true' === $plugin->get('include_sessions');
$includeServices = 'true' === $plugin->get('include_services');

if (!$includeServices) {
    api_not_allowed(true);
}

$taxEnable = 'true' === $plugin->get('tax_enable');

api_protect_admin_script(true);

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$currentPage = max(1, $httpRequest->query->getInt('page', 1));
$first = $pageSize * ($currentPage - 1);

$services = $plugin->getServices($first, $pageSize);
$totalItems = (int) $plugin->getServices(0, 1000000000, 'count');
$pagesCount = $totalItems > 0 ? (int) ceil($totalItems / $pageSize) : 1;

$pagination = BuyCoursesPlugin::returnPagination(api_get_self(), $currentPage, $pagesCount, $totalItems);

// breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php',
    'name' => $plugin->get_lang('plugin_title'),
];

$templateName = $plugin->get_lang('Services');

$defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $defaultBackUrl;

$tpl = new Template($templateName);

$tpl->assign('page_title', $templateName);
$tpl->assign('plugin_title', $plugin->get_lang('plugin_title'));
$tpl->assign('back_url', $backUrl);

$tpl->assign('product_type_course', BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
$tpl->assign('product_type_session', BuyCoursesPlugin::PRODUCT_TYPE_SESSION);

$tpl->assign('showing_courses', false);
$tpl->assign('showing_sessions', false);
$tpl->assign('showing_services', true);

$tpl->assign('show_courses_tab', false);
$tpl->assign('show_sessions_tab', false);
$tpl->assign('show_services_tab', true);

$tpl->assign('sessions_are_included', $includeSession);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('tax_enable', $taxEnable);

$tpl->assign('courses', []);
$tpl->assign('sessions', []);
$tpl->assign('services', $services);

$tpl->assign('course_pagination', '');
$tpl->assign('session_pagination', '');
$tpl->assign('service_pagination', $pagination);

$tpl->assign('course_current_page', 1);
$tpl->assign('course_pages_count', 1);
$tpl->assign('course_total_items', 0);

$tpl->assign('session_current_page', 1);
$tpl->assign('session_pages_count', 1);
$tpl->assign('session_total_items', 0);

$tpl->assign('service_current_page', $currentPage);
$tpl->assign('service_pages_count', $pagesCount);
$tpl->assign('service_total_items', $totalItems);

if ($taxEnable) {
    $globalParameters = $plugin->getGlobalParameters();
    $tpl->assign('global_tax_perc', $globalParameters['global_tax_perc'] ?? 0);
    $tpl->assign('tax_applies_to', $globalParameters['tax_applies_to'] ?? 0);
    $tpl->assign('tax_name', $globalParameters['tax_name'] ?? '');
}

$content = $tpl->fetch('BuyCourses/view/list.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
