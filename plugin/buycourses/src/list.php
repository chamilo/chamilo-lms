<?php
/**
 * List of courses
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */

require_once '../../../main/inc/global.inc.php';
require_once 'buy_course.lib.php';

$plugin = BuyCoursesPlugin::create();
$includeSessions = $plugin->get('include_sessions') === 'true';

if (api_is_platform_admin()) {
    $interbreadcrumb[] = array("url" => "configuration.php", "name" => $plugin->get_lang('AvailableCoursesConfiguration'));
    $interbreadcrumb[] = array("url" => "paymentsetup.php", "name" => $plugin->get_lang('PaymentsConfiguration'));
}

$templateName = $plugin->get_lang('CourseListOnSale');
$tpl = new Template($templateName);

$courseList = $plugin->getCatalogCourseList();
$sessionList = [];
$currency = $plugin->getSelectedCurrency();
$currencyCode = null;

if (isset($currency['currency_code'])) {
    $currencyCode = $currency['currency_code'];
}

$tpl->assign('courses', $courseList);
$tpl->assign('currency', $currencyCode);
$tpl->assign('sessions_are_included', $includeSessions);

if ($includeSessions) {
    $sessionList = $plugin->getCatalogSessionList();
    $tpl->assign('sessions', $sessionList);
}

$content = $tpl->fetch('buycourses/view/list.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
