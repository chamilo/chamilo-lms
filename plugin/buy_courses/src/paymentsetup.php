<?php
/**
 * Initialization
 */
require_once dirname(__FILE__) . '/buy_course.lib.php';
require_once '../../../main/inc/global.inc.php';
require_once 'buy_course_plugin.class.php';
$plugin = Buy_CoursesPlugin::create();
$_cid = 0;
$templateName = $plugin->get_lang('PaymentConfiguration');
$interbreadcrumb[] = array("url" => "list.php", "name" => $plugin->get_lang('CourseListOnSale'));
$interbreadcrumb[] = array("url" => "configuration.php", "name" => $plugin->get_lang('AvailableCoursesConfiguration'));

$tpl = new Template($templateName);
$teacher = api_is_platform_admin();
api_protect_course_script(true);

if ($teacher) {
    // Sync course table with the plugin
    $listCurrency = listCurrency();
    $paypalParams = paypalParameters();
    $transferenceParams = transferenceParameters();

    $confirmationImg = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/message_confirmation.png';
    $saveImg = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/save.png';
    $moreImg = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/more.png';
    $deleteImg = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/delete.png';
    $showImg = api_get_path(WEB_PLUGIN_PATH) . 'buy_courses/resources/acces_tool.gif';

    $paypalEnable = $plugin->get('paypal_enable');
    $transferenceEnable = $plugin->get('transference_enable');

    $tpl->assign('server', $_configuration['root_web']);
    $tpl->assign('currencies', $listCurrency);
    $tpl->assign('paypal', $paypalParams);
    $tpl->assign('transference', $transferenceParams);
    $tpl->assign('confirmation_img', $confirmationImg);
    $tpl->assign('save_img', $saveImg);
    $tpl->assign('more_img', $moreImg);
    $tpl->assign('delete_img', $deleteImg);
    $tpl->assign('show_img', $showImg);
    $tpl->assign('paypal_enable', $paypalEnable);
    $tpl->assign('transference_enable', $transferenceEnable);

    $listing_tpl = 'buy_courses/view/paymentsetup.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
