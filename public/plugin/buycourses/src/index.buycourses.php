<?php
/* For license terms, see /license.txt */

/**
 * Index of the Buy Courses plugin courses list.
 *
 * @package chamilo.plugin.buycourses
 */
$plugin = BuyCoursesPlugin::create();
$allow = $plugin->get('unregistered_users_enable');

if (($allow === 'true' && api_is_anonymous()) || !api_is_anonymous()) {
    $tpl = new Template();
    $content = $tpl->fetch('buycourses/view/index.tpl');
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
