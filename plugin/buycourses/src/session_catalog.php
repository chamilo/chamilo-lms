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

if (!$includeSessions) {
    api_not_allowed(true);
}

$nameFilter = null;
$minFilter = 0;
$maxFilter = 0;
$sessionCategory = isset($_GET['session_category']) ? (int) $_GET['session_category'] : 0;
$form = new FormValidator(
    'search_filter_form',
    'get',
    null,
    null,
    [],
    FormValidator::LAYOUT_INLINE
);

$form->removeAttribute('class');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $nameFilter = isset($formValues['name']) ? $formValues['name'] : null;
    $minFilter = isset($formValues['min']) ? $formValues['min'] : 0;
    $maxFilter = isset($formValues['max']) ? $formValues['max'] : 0;
    $sessionCategory = isset($formValues['session_category']) ? $formValues['session_category'] : $sessionCategory;
}

$form->addHeader($plugin->get_lang('SearchFilter'));

$categoriesOptions = [
    '0' => get_lang('AllCategories'),
];
$categoriesList = SessionManager::get_all_session_category();
if ($categoriesList != false) {
    foreach ($categoriesList as $categoryItem) {
        $categoriesOptions[$categoryItem['id']] = $categoryItem['name'];
    }
}
$form->addSelect(
    'session_category',
    get_lang('SessionCategory'),
    $categoriesOptions,
    [
        'id' => 'session_category',
    ]
);

$form->addText('name', get_lang('SessionName'), false);

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

$form->setDefaults(
    [
        'session_category' => $sessionCategory,
    ]
);
$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$first = $pageSize * ($currentPage - 1);
$sessionList = $plugin->getCatalogSessionList($first, $pageSize, $nameFilter, $minFilter, $maxFilter, 'all', $sessionCategory);
$totalItems = $plugin->getCatalogSessionList($first, $pageSize, $nameFilter, $minFilter, $maxFilter, 'count', $sessionCategory);
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
}

$templateName = $plugin->get_lang('CourseListOnSale');

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'buycourses/resources/css/style.css');

$template = new Template($templateName);
$template->assign('search_filter_form', $form->returnForm());
$template->assign('sessions_are_included', $includeSessions);
$template->assign('services_are_included', $includeServices);
$template->assign('showing_sessions', true);
$template->assign('sessions', $sessionList);
$template->assign('pagination', $pagination);

$countCourses = $plugin->getCatalogCourseList($first, $pageSize, $nameFilter, $minFilter, $maxFilter, 'count');

$template->assign('coursesExist', $countCourses > 0);
$template->assign('sessionExist', true);

$content = $template->fetch('buycourses/view/catalog.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
