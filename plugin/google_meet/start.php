<?php
/**
 * This script initiates a video conference session, calling the Google Meet.
 */
require_once __DIR__.'/../../vendor/autoload.php';

$course_plugin = 'google_meet'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(
        WEB_PLUGIN_PATH
    ).'google_meet/resources/css/style.css"/>';

$plugin = GoogleMeetPlugin::create();

$tool_name = $plugin->get_lang('plugin_title');
$tpl = new Template($tool_name);
$message = null;
$userId = api_get_user_id();

$courseInfo = api_get_course_info();
$isTeacher = api_is_teacher();
$isAdmin = api_is_platform_admin();
$isStudent = api_is_student();

$action = isset($_GET['action']) ? $_GET['action'] : null;
$enable = $plugin->get('google_meet_enabled') == 'true';

$urlAddMeet = api_get_path(WEB_PLUGIN_PATH).'google_meet/meets.php?action=add&'.api_get_cidreq();

if ($enable) {
    if ($isAdmin || $isTeacher || $isStudent) {
        $meets = $plugin->listMeets($courseInfo['real_id'], api_get_session_id());
    }
}

$tpl->assign('url_add_room', $urlAddMeet);
$tpl->assign('meets', $meets);
$tpl->assign('is_admin', $isAdmin);
$tpl->assign('is_student', $isStudent);
$tpl->assign('is_teacher', $isTeacher);
$content = $tpl->fetch('google_meet/view/home.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
