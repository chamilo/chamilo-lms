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
    // The conference is running, everything is fine, join
    header('location: '.wc_joinMeetingURL($bbb_host,$bbb_salt,$full_user_name,$meeting_name,($teacher?$meeting_mod_pw:$meeting_att_pw),$user_id));
    exit;
} else { //$is_running = false or 'false'
    // The conference room does not seem to be running...
    // First, try harder and ignore the "running" status
    $meetings = wc_getRunningMeetings($bbb_host,$bbb_salt);
    $found = false;
    foreach ($meetings as $meeting) {
        //Try to find our meeting room in the list...
        if ($meeting['meetingID'] == $meeting_name) {
            $meeting_info = wc_getMeetingInfo($bbb_host,$bbb_salt,$meeting_name,$meeting_mod_pw);
            error_log('Found passive meeting created '.($meeting_info['createTime']).' seconds ago with '.count($meeting_info['attendees']).' attendees - joining as '.($teacher?'teacher':'student'));
            //if the user is a teacher, or if there are already attendees in
            // the conference room, then allow joining it
            if ($teacher or count($meeting_info['attendees'])>0) {
                header('location: '.wc_joinMeetingURL($bbb_host,$bbb_salt,$full_user_name,$meeting_name,($teacher?$meeting_mod_pw:$meeting_att_pw),$user_id));
                exit; 
            }
        }
    }
    // That conference room is really not running or it has no
    // accompanying moderator subscribed
    if ($teacher) {
        // The user is a teacher, so he has the right to create the
        //  room, so create it and join it
        wc_createMeeting($bbb_host,$bbb_salt,$meeting_name,$meeting_name,$meeting_att_pw,$meeting_mod_pw,$meeting_wel_ms,api_get_path(WEB_COURSE_PATH).'/'.$ccode);
        header('location: '.wc_joinMeetingURL($bbb_host,$bbb_salt,$full_user_name,$meeting_name,($teacher?$meeting_mod_pw:$meeting_att_pw),$user_id));
        exit;
    } else {
        // There is no conference room for this course and the user
        // is a mere student, so he cannot start a conference room by
        // himself: a teacher has to launch it first
        header('location: '.api_get_path(WEB_COURSE_PATH).'/'.$ccode);
        exit;
    }
}
