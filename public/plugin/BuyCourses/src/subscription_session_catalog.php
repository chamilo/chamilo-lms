<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * List of subscription sessions.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSessions = 'true' === $plugin->get('include_sessions');
$includeServices = 'true' === $plugin->get('include_services');

if (!$includeSessions) {
    api_not_allowed(true);
}

$nameFilter = '';
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
    $nameFilter = isset($formValues['name']) ? trim((string) $formValues['name']) : '';
    $sessionCategory = isset($formValues['session_category'])
        ? (int) $formValues['session_category']
        : $sessionCategory;
}

$form->addHeader($plugin->get_lang('SearchFilter'));

$categoriesOptions = [
    '0' => get_lang('AllCategories'),
];

$categoriesList = SessionManager::get_all_session_category();
if (false != $categoriesList) {
    foreach ($categoriesList as $categoryItem) {
        $categoriesOptions[(int) $categoryItem['id']] = $categoryItem['name'];
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

$form->setDefaults([
    'session_category' => $sessionCategory,
    'name' => $nameFilter,
]);

$pageSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$first = $pageSize * ($currentPage - 1);

$sessionList = $plugin->getCatalogSubscriptionSessionList(
    $first,
    $pageSize,
    $nameFilter,
    'all',
    $sessionCategory
);

$totalItems = (int) $plugin->getCatalogSubscriptionSessionList(
    $first,
    $pageSize,
    $nameFilter,
    'count',
    $sessionCategory
);

$pagesCount = $totalItems > 0 ? (int) ceil($totalItems / $pageSize) : 1;

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
$template = new Template($templateName);
$defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $defaultBackUrl;

/*
 * Tabs should depend on the available catalog sections, not on the current
 * filter results. Otherwise, the Courses tab disappears when the current
 * search does not return items while browsing the Sessions catalog.
 */
$coursesExist = true;
$sessionExist = true;

$template->assign('page_title', $templateName);
$template->assign('back_url', $backUrl);

$template->assign('search_filter_form', $form->returnForm());
$template->assign('sessions_are_included', $includeSessions);
$template->assign('services_are_included', $includeServices);

$template->assign('showing_courses', false);
$template->assign('showing_sessions', true);

$template->assign('courses', []);
$template->assign('sessions', $sessionList);

$template->assign('coursesExist', $coursesExist);
$template->assign('sessionExist', $sessionExist);

$template->assign('pagination_current_page', $currentPage);
$template->assign('pagination_pages_count', $pagesCount);
$template->assign('pagination_total_items', $totalItems);
$template->assign('pagination_base_path', 'subscription_session_catalog.php');
$template->assign('name_filter_value', $nameFilter);
$template->assign('session_category_value', $sessionCategory);

$content = $template->fetch('BuyCourses/view/subscription_catalog.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
