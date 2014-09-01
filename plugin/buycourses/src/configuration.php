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

$_cid = 0;
$templateName = $plugin->get_lang('AvailableCourses');
$interbreadcrumb[] = array("url" => "list.php", "name" => $plugin->get_lang('CourseListOnSale'));
$interbreadcrumb[] = array("url" => "paymentsetup.php", "name" => get_lang('Configuration'));

$tpl = new Template($templateName);

$teacher = api_is_platform_admin();
api_protect_course_script(true);

if ($teacher) {
    // sync course table with the plugin
    sync();
    $visibility = array();
    $visibility[] = getCourseVisibilityIcon('0');
    $visibility[] = getCourseVisibilityIcon('1');
    $visibility[] = getCourseVisibilityIcon('2');
    $visibility[] = getCourseVisibilityIcon('3');

    $coursesList = listCourses();
    $confirmationImgPath = api_get_path(WEB_PLUGIN_PATH) . 'buycourses/resources/message_confirmation.png';
    $saveImgPath = api_get_path(WEB_PLUGIN_PATH) . 'buycourses/resources/save.png';
    $currencyType = findCurrency();

    $tpl->assign('server', $_configuration['root_web']);
    $tpl->assign('courses', $coursesList);
    $tpl->assign('visibility', $visibility);
    $tpl->assign('confirmation_img', $confirmationImgPath);
    $tpl->assign('save_img', $saveImgPath);
    $tpl->assign('currency', $currencyType);

    $listing_tpl = 'buycourses/view/configuration.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
