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
require_once 'bbb_api.php';

$course_code = api_get_course_id();

$meeting_params = array();
$meeting_params['meeting_name'] = api_get_course_id();

$bbb = new bbb();

if ($bbb) {
    if ($bbb->is_meeting_exist($meeting_params['meeting_name'])) {
        $url = $bbb->join_meeting($meeting_params['meeting_name']);        
        if ($url) {
            header('location: '.$url);
            exit;
        } else {
            $url = $bbb->create_meeting($meeting_params);
            header('location: '.$url);
            exit;
        }        
    } else {
        $url = $bbb->create_meeting($meeting_params);
        header('location: '.$url);
        exit;
    }
} else {
    if (api_is_platform_admin()) {
        Display::display_warning_message(get_lang('NotConfigured'));
    }
}

header('location: '.api_get_path(WEB_COURSE_PATH).'/'.$course_code);
exit;

/*
$teacher = api_is_course_admin() || api_is_coach() || api_is_platform_admin();
$user_info = api_get_user_info(api_get_user_id());
$full_user_name = api_get_person_name($user_info['firstname'],$user_info['lastname']);
$user_id = api_get_user_id();

$is_running = wc_isMeetingRunning($bbb_host, $bbb_salt, $meeting_name);

if ($is_running == 'true') {
    // The conference is running, everything is fine, join
    header('location: '.wc_joinMeetingURL($bbb_host,$bbb_salt,$full_user_name,$meeting_name,($teacher?$meeting_mod_pw:$meeting_att_pw),$user_id));
    exit;
} else { //$is_running = false or 'false'
    // The conference room does not seem to be running...
    // First, try harder and ignore the "running" status
    $meetings = wc_getRunningMeetings($bbb_host,$bbb_salt);
    
    if (!empty($meetings)) {
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
    }
    
    // That conference room is really not running or it has no accompanying moderator subscribed
    //if (1) {
    
    if ($teacher) {
        // The user is a teacher, so he has the right to create the
        //  room, so create it and join it
        //var_dump($bbb_host,$bbb_salt,$meeting_name,$meeting_name,$meeting_att_pw,$meeting_mod_pw,$meeting_wel_ms,api_get_path(WEB_COURSE_PATH).'/'.$course_code);    
        $result = wc_createMeeting($bbb_host, $bbb_salt, $meeting_name, $meeting_name, $meeting_att_pw, $meeting_mod_pw, $meeting_wel_ms, api_get_path(WEB_COURSE_PATH).'/'.$course_code, $record_conference);
        if ($result) {
            header('location: '.wc_joinMeetingURL($bbb_host,$bbb_salt,$full_user_name,$meeting_name,($teacher?$meeting_mod_pw:$meeting_att_pw),$user_id));        
            exit;
        }
    }
    // There is no conference room for this course and the user
    // is a mere student, so he cannot start a conference room by
    // himself: a teacher has to launch it first
    
    header('location: '.api_get_path(WEB_COURSE_PATH).'/'.$course_code);
    exit;
}
*/