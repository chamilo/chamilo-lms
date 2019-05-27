<?php
/* For license terms, see /license.txt */

/**
 * Index of the Buy Courses plugin courses list.
 *
 * @package chamilo.plugin.buycourses
 */
$plugin = BuyCoursesPlugin::create();
$guess_enable = $plugin->get('unregistered_users_enable');

if ($guess_enable == 'true' || isset($_SESSION['_user'])) {
    if (api_is_platform_admin()) {
        $tpl = new Template();
        $content = $tpl->fetch('buycourses/view/index.tpl');
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
    }
}
