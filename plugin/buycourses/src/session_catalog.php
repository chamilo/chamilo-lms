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
$form->addText('name', get_lang('Session name'), false);
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
$sessionList = $plugin->getCatalogSessionList($first, $pageSize, $nameFilter, $minFilter, $maxFilter);
$totalItems = $plugin->getCatalogSessionList($first, $pageSize, $nameFilter, $minFilter, $maxFilter, 'count');
$pagesCount = ceil($totalItems / $pageSize);
$url = api_get_self().'?';
$pagination = Display::getPagination($url, $currentPage, $pagesCount, $totalItems);

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

$template = new Template($templateName);
$template->assign('search_filter_form', $form->returnForm());
$template->assign('sessions_are_included', $includeSessions);
$template->assign('services_are_included', $includeServices);
$template->assign('showing_sessions', true);
$template->assign('sessions', $sessionList);
$template->assign('pagination', $pagination);

$content = $template->fetch('buycourses/view/catalog.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
