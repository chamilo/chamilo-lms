<?php
/**
 * This script initiates a videoconference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization
 */

$course_plugin = 'openmeetings'; //needed in order to load the plugin lang variables
require_once dirname(__FILE__).'/config.php';
$tool_name = get_lang('Videoconference');
$tpl = new Template($tool_name);
$om = new OpenMeetings();

if ($om->plugin_enabled) {

    if ($om->isServerRunning()) {

        if (isset($_GET['launch']) && $_GET['launch'] == 1) {

            $meeting_params = array();
            $meeting_params['meeting_name'] = api_get_course_id().'-'.api_get_session_id();
            $meetings = $om->getCourseMeetings();

            // Select the meeting with more participantCount.
            $selectedMeeting = array();
            if (!empty($meetings)) {
                $max = 0;
                foreach ($meetings as $meeting) {
                    if ($meeting['participantCount'] > $max) {
                        $selectedMeeting = $meeting;
                        $max = $meeting['participantCount'];
                    }
                }
            }

            if ($om->loginUser() && !empty($selectedMeeting)) {
            //if (false/*$om->meeting_exists($meeting_params['meeting_name'])*/) {
                $url = $om->joinMeeting($selectedMeeting['id']);
                if ($url) {
                    header('location: '.$url);
                    exit;
                }
            } else {

                if ( $om->isTeacher() && $om->loginUser()) {

                    //$url =
                    $om->createMeeting($meeting_params);
                    //header('location: '.$url);
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
} else {
    $message = Display::return_message(get_lang('ServerIsNotConfigured'), 'warning');
}
$tpl->assign('message', $message);
$tpl->display_one_col_template();
