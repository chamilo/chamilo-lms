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

if (!$includeSessions) {
    api_not_allowed(true);
}

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

$sessionList = $plugin->getCatalogSessionList($nameFilter, $minFilter, $maxFilter);

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
}

$templateName = $plugin->get_lang('CourseListOnSale');

$template = new Template($templateName);
$template->assign('search_filter_form', $form->returnForm());
$template->assign('sessions_are_included', $includeSessions);
$template->assign('showing_sessions', true);
$template->assign('sessions', $sessionList);

$content = $template->fetch('buycourses/view/catalog.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
