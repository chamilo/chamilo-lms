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
    $tpl->assign('BuySessions', $plugin->get_lang('BuySessions'));
    $tpl->assign('BuyCourses', $templateName);
    $tpl->assign('ConfigurationOfSessionsAndPrices', $plugin->get_lang('ConfigurationOfSessionsAndPrices'));
    $tpl->assign('ConfigurationOfCoursesAndPrices', $plugin->get_lang('ConfigurationOfCoursesAndPrices'));
    $tpl->assign('ConfigurationOfPayments', $plugin->get_lang('ConfigurationOfPayments'));
    $tpl->assign('OrdersPendingOfPayment', $plugin->get_lang('OrdersPendingOfPayment'));
    $listing_tpl = 'buycourses/view/index.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    preg_match_all('/src\/.*\.php/', $content, $matches);
    count($matches[0]) > 1 ? $tpl->display_one_col_template() : header('Location: '.$matches[0][0]);
}
