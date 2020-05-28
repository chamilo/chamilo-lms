<?php
/**
 * This script initiates a video conference session, calling the Google Meet.
 */
require_once __DIR__.'/../../vendor/autoload.php';

$course_plugin = 'zoom'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(
        WEB_PLUGIN_PATH
    ).'googlemeet/resources/css/style.css"/>';

$plugin = GoogleMeetPlugin::create();

$tool_name = $plugin->get_lang('tool_title');
$tpl = new Template($tool_name);
$message = null;
$userId = api_get_user_id();

$courseInfo = api_get_course_info();
$isTeacher = api_is_teacher();
$isAdmin = api_is_platform_admin();
$isStudent = api_is_student();

$action = isset($_GET['action']) ? $_GET['action'] : null;
$enable = $plugin->get('google_meet_enabled') == 'true';

if ($enable) {
    if ($isAdmin || $isTeacher || $isStudent) {

    }
}


$content = $tpl->fetch('googlemeet/view/start.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();