<?php
/**
 * This script initiates a videoconference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization
 */
require_once '../../main/inc/global.inc.php';
require_once 'bbb.lib.php';
//The script receives the course_code (cidReq), which allows it to get the corresponding data
$cid = api_get_real_course_id();
$ccode = api_get_course_id();
// initialize conference settings from course settings
$meeting_name = api_get_course_setting('big_blue_button_meeting_name',$ccode);
if (empty($meeting_name) or $meeting_name==-1) { $meeting_name = $ccode; }
$meeting_att_pw = api_get_course_setting('big_blue_button_meeting_attendee_pw',$ccode);
if (empty($meeting_att_pw) or $meeting_att_pw==-1) { $meeting_att_pw = $ccode; }
$meeting_mod_pw = api_get_course_setting('big_blue_button_meeting_moderator_pw',$ccode);
if (empty($meeting_mod_pw) or $meeting_mod_pw==-1) { $meeting_mod_pw = $ccode.'mod'; }
$meeting_wel_ms = api_get_course_setting('big_blue_button_meeting_welcome_message',$ccode);
if (empty($meeting_wel_ms) or $meeting_wel_ms==-1) { $meeting_wel_ms = ''; }

// initialize video server settings from global settings
$settings = api_get_settings('Extra','list',api_get_current_access_url_id());
$bbb_settings = array();
foreach ($settings as $setting) {
    if (substr($setting['variable'],0,4)==='bbb_') {
        $bbb_settings[$setting['variable']] = $setting['selected_value'];
    }
}
$bbb_plugin = $bbb_settings['bbb_plugin'] === 'true';
$bbb_host = $bbb_settings['bbb_plugin_host'];
$bbb_salt = $bbb_settings['bbb_plugin_salt'];

if (!$bbb_plugin) {
    //the BigBlueButton plugin is not enabled (strangely), return to course homepage
    header('location: '.api_get_path(WEB_COURSE_PATH).'/'.$ccode);
}
$teacher = api_is_course_admin() || api_is_coach() || api_is_platform_admin();
$user_info = api_get_user_info(api_get_user_id());
$full_user_name = api_get_person_name($user_info['firstname'],$user_info['lastname']);
$user_id = api_get_user_id();

$is_running = wc_isMeetingRunning($bbb_host,$bbb_salt,$meeting_name);
if ($is_running == 'true') {
    header('location: '.wc_joinMeetingURL($bbb_host,$bbb_salt,$full_user_name,$meeting_name,($teacher?$meeting_mod_pw:$meeting_att_pw),$user_id));
} else { //$is_running = false or 'false'
    if ($teacher) {
        wc_createMeeting($bbb_host,$bbb_salt,$meeting_name,$meeting_name,$meeting_att_pw,$meeting_mod_pw,$meeting_wel_ms,api_get_path(WEB_COURSE_PATH).'/'.$ccode);
        header('location: '.wc_joinMeetingURL($bbb_host,$bbb_salt,$full_user_name,$meeting_name,$meeting_mod_pw,$user_id));
    } else {
        header('location: '.api_get_path(WEB_COURSE_PATH).'/'.$ccode);
    }
}
