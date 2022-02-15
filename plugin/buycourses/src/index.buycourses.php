<?php

/* For license terms, see /license.txt */

/**
 * Index of the Buy Courses plugin courses list.
 */
$plugin = BuyCoursesPlugin::create();
$allow = $plugin->get('unregistered_users_enable');

$userIsAdmin = api_is_platform_admin();

if (($allow === 'true' && api_is_anonymous()) || !api_is_anonymous()) {
    $webPluginPath = api_get_path(WEB_PLUGIN_PATH).'buycourses/';

    $countCourses = $plugin->getCatalogCourseList(
        0,
        BuyCoursesPlugin::PAGINATION_PAGE_SIZE,
        null,
        0,
        0,
        'count'
    );

    if ($countCourses > 0 && !$userIsAdmin) {
        api_location($webPluginPath.'src/course_catalog.php');
    }

    $countSessions = $plugin->getCatalogSessionList(
        0,
        BuyCoursesPlugin::PAGINATION_PAGE_SIZE,
        null,
        0,
        0,
        'count'
    );

    if ($countSessions > 0 && !$userIsAdmin) {
        api_location($webPluginPath.'src/session_catalog.php');
    }

    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'buycourses/resources/css/style.css');

    $tpl = new Template();
    $content = $tpl->fetch('buycourses/view/index.tpl');
    $tpl->assign('content', $content);
    $tpl->display_one_col_template(false);
}
