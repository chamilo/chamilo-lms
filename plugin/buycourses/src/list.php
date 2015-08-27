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

if (isset($_SESSION['bc_success'])) {
    $tpl->assign('rmessage', 'YES');
    if ($_SESSION['bc_success'] == true) {
        $message = sprintf($plugin->get_lang($_SESSION['bc_message']), $_SESSION['bc_url']);
        unset($_SESSION['bc_url']);
        $tpl->assign('class', 'confirmation-message');
    } else {
        $message = $plugin->get_lang($_SESSION['bc_message']);
        $tpl->assign('class', 'warning-message');
    }
    $tpl->assign('responseMessage', $message);
    unset($_SESSION['bc_success']);
    unset($_SESSION['bc_message']);

} else {
    $tpl->assign('rmessage', 'NO');
}

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
    $sessionList = $plugin->getUserSessionList();
    $tpl->assign('sessions', $sessionList);
}

$content = $tpl->fetch('buycourses/view/list.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
