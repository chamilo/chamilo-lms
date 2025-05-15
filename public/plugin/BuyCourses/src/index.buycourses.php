<?php
/* For license terms, see /license.txt */

/**
 * Index of the Buy Courses plugin courses list.
 */
$plugin = BuyCoursesPlugin::create();
$allow = $plugin->get('unregistered_users_enable');

if (('true' === $allow && api_is_anonymous()) || !api_is_anonymous()) {
    $tpl = new Template();
    $content = $tpl->fetch('BuyCourses/view/index.tpl');
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
