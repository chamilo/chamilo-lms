<?php
/* For license terms, see /license.txt */

/**
 * List of courses.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSessions = $plugin->get('include_sessions') === 'true';
$includeServices = $plugin->get('include_services') === 'true';

$nameFilter = '';
$minFilter = 0;
$maxFilter = 0;

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
    $minFilter = isset($formValues['min']) ? $formValues['min'] : 0;
    $maxFilter = isset($formValues['max']) ? $formValues['max'] : 0;
}

$form->addHeader($plugin->get_lang('SearchFilter'));
$form->addText('name', get_lang('CourseName'), false);
$form->addElement(
    'number',
    'min',
    $plugin->get_lang('MinimumPrice'),
    ['step' => '0.01', 'min' => '0']
);
$form->addElement(
    'number',
    'max',
    $plugin->get_lang('MaximumPrice'),
    ['step' => '0.01', 'min' => '0']
);
$form->addHtml('<hr>');
$form->addButtonFilter(get_lang('Search'));

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$first = $pageSize * ($currentPage - 1);
$courseList = $plugin->getCatalogCourseList($first, $pageSize, $nameFilter, $minFilter, $maxFilter);
$totalItems = $plugin->getCatalogCourseList($first, $pageSize, $nameFilter, $minFilter, $maxFilter, 'count');
$pagesCount = ceil($totalItems / $pageSize);

$pagination = BuyCoursesPlugin::returnPagination(api_get_self(), $currentPage, $pagesCount, $totalItems);

// View
if (api_is_platform_admin()) {
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
        'url' => 'course_panel.php',
        'name' => get_lang('TabsDashboard'),
    ];
}

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'buycourses/resources/css/style.css');

$templateName = $plugin->get_lang('CourseListOnSale');
$tpl = new Template($templateName);
$tpl->assign('search_filter_form', $form->returnForm());
$tpl->assign('showing_courses', true);
$tpl->assign('courses', $courseList);
$tpl->assign('sessions_are_included', $includeSessions);
$tpl->assign('services_are_included', $includeServices);
$tpl->assign('pagination', $pagination);

$countSessions = $plugin->getCatalogSessionList($first, $pageSize, $nameFilter, $minFilter, $maxFilter, 'count');

$tpl->assign('coursesExist', true);
$tpl->assign('sessionExist', $countSessions > 0);

$content = $tpl->fetch('buycourses/view/catalog.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
