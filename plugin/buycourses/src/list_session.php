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

if (!$includeSession) {
    api_not_allowed(true);
}
$includeServices = $plugin->get('include_services') === 'true';
$taxEnable = $plugin->get('tax_enable') === 'true';

api_protect_admin_script(true);

Display::addFlash(
    Display::return_message(
        get_lang('Information').' - '.$plugin->get_lang('CoursesInSessionsDoesntDisplayHere'),
        'info'
    )
);

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$first = $pageSize * ($currentPage - 1);

// breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'buycourses/index.php',
    'name' => $plugin->get_lang('plugin_title'),
];

$templateName = $plugin->get_lang('AvailableCourses');

$tpl = new Template($templateName);

$tpl->assign('product_type_course', BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
$tpl->assign('product_type_session', BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
$tpl->assign('sessions_are_included', $includeSession);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('tax_enable', $taxEnable);

$query = CoursesAndSessionsCatalog::browseSessions(null, ['start' => $first, 'length' => $pageSize], true);
$sessions = new Paginator($query, $fetchJoinCollection = true);
foreach ($sessions as $session) {
    $item = $plugin->getItemByProduct($session->getId(), BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
    $session->buyCourseData = [];
    if ($item !== false) {
        $session->buyCourseData = $item;
    }
}

$totalItems = count($sessions);
$pagesCount = ceil($totalItems / $pageSize);

$url = api_get_self().'?type='.BuyCoursesPlugin::PRODUCT_TYPE_SESSION;
$pagination = Display::getPagination($url, $currentPage, $pagesCount, $totalItems);

$tpl->assign('sessions', $sessions);
$tpl->assign('session_pagination', $pagination);

if ($taxEnable) {
    $globalParameters = $plugin->getGlobalParameters();
    $tpl->assign('global_tax_perc', $globalParameters['global_tax_perc']);
    $tpl->assign('tax_applies_to', $globalParameters['tax_applies_to']);
    $tpl->assign('tax_name', $globalParameters['tax_name']);
}

$content = $tpl->fetch('buycourses/view/list.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
