<?php

/* For license terms, see /license.txt */

/**
 * Index of the Buy Courses plugin courses list.
 */
$plugin = BuyCoursesPlugin::create();
$allow = $plugin->get('unregistered_users_enable');

if (($allow === 'true' && api_is_anonymous()) || !api_is_anonymous()) {
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'buycourses/resources/css/style.css');

    $tpl = new Template();
    $content = $tpl->fetch('buycourses/view/index.tpl');
    $tpl->assign('content', $content);
    //$tpl->display_one_col_template();
    $tpl->display($tpl->get_template('layout/layout_1_col.tpl'), false);
}
