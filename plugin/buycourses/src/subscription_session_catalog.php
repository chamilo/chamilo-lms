<?php
/* For license terms, see /license.txt */

/**
 * List of sessions.
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
$sessionList = $plugin->getCatalogSubscriptionSessionList($first, $pageSize, $nameFilter, 'all', $sessionCategory);
$totalItems = $plugin->getCatalogSubscriptionSessionList($first, $pageSize, $nameFilter, 'count', $sessionCategory);
$pagesCount = ceil($totalItems / $pageSize);
$pagination = BuyCoursesPlugin::returnPagination(api_get_self(), $currentPage, $pagesCount, $totalItems);

// View
if (api_is_platform_admin()) {
    $interbreadcrumb[] = [
        'url' => 'subscriptions_sessions.php',
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
$template->assign('showing_sessions', true);
$template->assign('sessions', $sessionList);
$template->assign('pagination', $pagination);

$courseList = $plugin->getCatalogSubscriptionCourseList($first, $pageSize, $nameFilter);
$coursesExist = true;
$sessionExist = true;
if (count($courseList) <= 0) {
    $coursesExist = false;
}
$template->assign('coursesExist', $coursesExist);
$template->assign('sessionExist', $sessionExist);

$content = $template->fetch('buycourses/view/subscription_catalog.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
