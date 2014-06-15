<?php
/**
 * List of courses
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */

require_once '../../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once 'buy_course_plugin.class.php';
require_once 'buy_course.lib.php';

$course_plugin = 'buycourses';
$plugin = BuyCoursesPlugin::create();
$_cid = 0;

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

$courseList = userCourseList();
$categoryList = listCategories();
$currencyType = findCurrency();

$tpl->assign('server', $_configuration['root_web']);
$tpl->assign('courses', $courseList);
$tpl->assign('category', $categoryList);
$tpl->assign('currency', $currencyType);

$listing_tpl = 'buycourses/view/list.tpl';
$content = $tpl->fetch($listing_tpl);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
