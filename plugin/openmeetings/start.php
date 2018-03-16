<?php
/**
 * This script initiates a video conference session.
 */
/**
 * Initialization.
 */
$course_plugin = 'openmeetings'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';
$tool_name = get_lang('Videoconference');
$tpl = new Template($tool_name);
$om = new \Chamilo\Plugin\OpenMeetings\OpenMeetings();

if ($om->isServerRunning()) {
    if (isset($_GET['launch']) && $_GET['launch'] == 1) {
        $meeting_params = [];
        $meeting_params['meeting_name'] = 'C'.api_get_course_id().'-'.api_get_session_id();
        $meetings = $om->getCourseMeetings();

        $selectedMeeting = [];
        /*
        // Select the meeting with more participantCount.
        if (!empty($meetings)) {
            $max = 0;
            foreach ($meetings as $meeting) {
                if ($meeting['participantCount'] > $max) {
                    $selectedMeeting = $meeting;
                    $max = $meeting['participantCount'];
                }
            }
        }
        */
        // Check for the first meeting available with status = 1
        // (there should be only one at a time, as createMeeting checks for that first
        if (!empty($meetings)) {
            foreach ($meetings as $meeting) {
                if ($meeting['status'] == 1) {
                    $selectedMeeting = $meeting;
                }
            }
        }

        if (!empty($selectedMeeting)) {
            $url = $om->joinMeeting($selectedMeeting['id']);
            if ($url) {
                header('location: '.$url);
                exit;
            }
        } else {
            if ($om->isTeacher()) {
                $om->createMeeting($meeting_params);
                exit;
            } else {
                $url = 'listing.php';
                header('location: '.$url);
                exit;
            }
        }
    } else {
        $url = 'listing.php';
        header('location: '.$url);
        exit;
    }
} else {
    $message = Display::return_message(get_lang('ServerIsNotRunning'), 'warning');
}
$tpl->assign('message', $message);
$tpl->display_one_col_template();
