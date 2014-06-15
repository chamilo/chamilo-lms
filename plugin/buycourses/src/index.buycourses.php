<?php
/* For license terms, see /license.txt */
/**
 * Index of the Buy Courses plugin courses list
 * @package chamilo.plugin.buycourses
 */
/**
 *
 */
$plugin = BuyCoursesPlugin::create();
$guess_enable = $plugin->get('unregistered_users_enable');

if ($guess_enable == "true" || isset($_SESSION['_user'])) {
    $title = $plugin->get_lang('CourseListOnSale');
    $templateName = $plugin->get_lang('BuyCourses');

    $tpl = new Template($templateName);
    $tpl->assign('isAdmin', api_is_platform_admin());
    $tpl->assign('title', $title);
    $tpl->assign('BuyCourses', $templateName);
    $tpl->assign('ConfigurationOfCoursesAndPrices', $plugin->get_lang('ConfigurationOfCoursesAndPrices'));
    $tpl->assign('ConfigurationOfPayments', $plugin->get_lang('ConfigurationOfPayments'));
    $tpl->assign('OrdersPendingOfPayment', $plugin->get_lang('OrdersPendingOfPayment'));
    $listing_tpl = 'buycourses/view/index.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
 
