<?php
/* For license terms, see /license.txt */

/**
 * List of subscriptions.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSessions = $plugin->get('include_sessions') === 'true';
$includeServices = $plugin->get('include_services') === 'true';

$nameFilter = '';

$form = new FormValidator(
    'search_filter_form',
    'get',
    null,
    null,
    [],
    FormValidator::LAYOUT_INLINE
);

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $nameFilter = isset($formValues['name']) ? $formValues['name'] : null;
}

$form->addHeader($plugin->get_lang('SearchFilter'));
$form->addText('name', get_lang('CourseName'), false);
$form->addHtml('<hr>');
$form->addButtonFilter(get_lang('Search'));

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$first = $pageSize * ($currentPage - 1);
$courseList = $plugin->getCatalogSubscriptionCourseList($first, $pageSize, $nameFilter);
$totalItems = $plugin->getCatalogSubscriptionCourseList($first, $pageSize, $nameFilter, 'count');
$pagesCount = ceil($totalItems / $pageSize);

$pagination = BuyCoursesPlugin::returnPagination(api_get_self(), $currentPage, $pagesCount, $totalItems);

// View
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

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'buycourses/resources/css/style.css');

$templateName = $plugin->get_lang('SubscriptionListOnSale');
$tpl = new Template($templateName);
$tpl->assign('search_filter_form', $form->returnForm());
$tpl->assign('showing_courses', true);
$tpl->assign('courses', $courseList);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('pagination', $pagination);

$sessionList = $plugin->getCatalogSubscriptionSessionList($first, $pageSize, $nameFilter, 'first', 0);
$coursesExist = true;
$sessionExist = true;
if (count($sessionList) <= 0) {
    $sessionExist = false;
}

$tpl->assign('coursesExist', $coursesExist);
$tpl->assign('sessionExist', $sessionExist);

$content = $tpl->fetch('buycourses/view/subscription_catalog.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
