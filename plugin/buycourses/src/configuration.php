<?php
/* For license terms, see /license.txt */
/**
 * Configuration script for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */
require_once dirname(__FILE__) . '/buy_course.lib.php';
require_once '../../../main/inc/global.inc.php';
require_once 'buy_course_plugin.class.php';

$plugin = BuyCoursesPlugin::create();
$includeSession = $plugin->get('include_sessions') === 'true';

api_protect_admin_script(true);

// sync course table with the plugin
BuyCoursesUtils::sync();

$visibility = array();
$visibility[] = getCourseVisibilityIcon('0');
$visibility[] = getCourseVisibilityIcon('1');
$visibility[] = getCourseVisibilityIcon('2');
$visibility[] = getCourseVisibilityIcon('3');
$visibility[] = getCourseVisibilityIcon('4');

$courses = $plugin->getCourses();
$currency = $plugin->getSelectedCurrency();
$currencyCode = null;

if (isset($currency['currency_code'])) {
    $currencyCode = $currency['currency_code'];
}

//view
$interbreadcrumb[] = [
    'url' => 'list.php',
    'name' => $plugin->get_lang('CourseListOnSale')
];
$interbreadcrumb[] = [
    'url' => 'paymentsetup.php',
    'name' => get_lang('Configuration')
];

$templateName = $plugin->get_lang('AvailableCourses');
$tpl = new Template($templateName);
$tpl->assign('courses', $courses);
$tpl->assign('visibility', $visibility);
$tpl->assign('currency', $currencyCode);
$tpl->assign('sessions_are_included', $includeSession);

if ($includeSession) {
    $sessions = $plugin->getSessions();

    $tpl->assign('sessions', $sessions);
}

$content = $tpl->fetch('buycourses/view/configuration.tpl');

$tpl->assign('header', $templateName);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
