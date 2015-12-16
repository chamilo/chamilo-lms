<?php
/**
 * List of courses
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */
$cidReset = true;

require_once '../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$includeSessions = $plugin->get('include_sessions') === 'true';

$nameFilter = null;
$minFilter = 0;
$maxFilter = 0;

$form = new FormValidator('search_filter_form', 'get', null, null, [], FormValidator::LAYOUT_INLINE);

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $nameFilter = isset($formValues['name']) ? $formValues['name'] : null;
    $minFilter = isset($formValues['min']) ? $formValues['min'] : 0;
    $maxFilter = isset($formValues['max']) ? $formValues['max'] : 0;
}

$form->addHeader($plugin->get_lang('SearchFilter'));
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

$courseList = $plugin->getCatalogCourseList($nameFilter, $minFilter, $maxFilter);

//View
if (api_is_platform_admin()) {
    $interbreadcrumb[] = [
        'url' => 'configuration.php',
        'name' => $plugin->get_lang('AvailableCoursesConfiguration')
    ];
    $interbreadcrumb[] = [
        'url' => 'paymentsetup.php',
        'name' => $plugin->get_lang('PaymentsConfiguration')
    ];
} else {
    $interbreadcrumb[] = [
        'url' => 'course_panel.php',
        'name' => get_lang('TabsDashboard')
    ];
}

$templateName = $plugin->get_lang('CourseListOnSale');
$tpl = new Template($templateName);
$tpl->assign('search_filter_form', $form->returnForm());
$tpl->assign('showing_courses', true);
$tpl->assign('courses', $courseList);
$tpl->assign('sessions_are_included', $includeSessions);

$content = $tpl->fetch('buycourses/view/catalog.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
