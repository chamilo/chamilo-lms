<?php
/* For license terms, see /license.txt */
/**
 * Configuration script for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */

use Doctrine\ORM\Tools\Pagination\Paginator;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSession = $plugin->get('include_sessions') === 'true';
$taxEnable = $plugin->get('tax_enable') === 'true';

api_protect_admin_script(true);

Display::addFlash(
    Display::return_message(
        get_lang('Info').' - '.$plugin->get_lang('CoursesInSessionsDoesntDisplayHere'),
        'info'
    )
);

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$type = isset($_GET['type']) ? (int) $_GET['type'] : BuyCoursesPlugin::PRODUCT_TYPE_COURSE;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$first = $pageSize * ($currentPage - 1);

$qb = $plugin->getCourseList($first, $pageSize);
$query = $qb->getQuery();
$courses = new Paginator($query, $fetchJoinCollection = true);
foreach ($courses as $course) {
    $item = $plugin->getSubscriptionItemByProduct($course->getId(), BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
    $course->buyCourseData = [];
    if ($item !== false) {
        $course->buyCourseData = $item;
    }
}

$totalItems = count($courses);
$pagesCount = ceil($totalItems / $pageSize);

$pagination = BuyCoursesPlugin::returnPagination(
    api_get_self(),
    $currentPage,
    $pagesCount,
    $totalItems,
    ['type' => $type]
);

// breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'buycourses/index.php',
    'name' => $plugin->get_lang('plugin_title'),
];

$templateName = $plugin->get_lang('AvailableCourses');

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'buycourses/resources/css/style.css');

$tpl = new Template($templateName);

$toolbar = Display::url(
    Display::returnFontAwesomeIcon('fa-calendar-alt').
    $plugin->get_lang('ConfigureSubscriptionsFrequencies'),
    api_get_path(WEB_PLUGIN_PATH).'buycourses/src/configure_frequency.php',
    ['class' => 'btn btn-primary']
);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);

$tpl->assign('product_type_course', BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
$tpl->assign('product_type_session', BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
$tpl->assign('courses', $courses);
$tpl->assign('course_pagination', $pagination);
$tpl->assign('sessions_are_included', $includeSession);
$tpl->assign('tax_enable', $taxEnable);

if ($taxEnable) {
    $globalParameters = $plugin->getGlobalParameters();
    $tpl->assign('global_tax_perc', $globalParameters['global_tax_perc']);
    $tpl->assign('tax_applies_to', $globalParameters['tax_applies_to']);
    $tpl->assign('tax_name', $globalParameters['tax_name']);
}

$content = $tpl->fetch('buycourses/view/subscriptions.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
