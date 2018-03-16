<?php
/**
 * This script initiates a videoconference session, calling the BigBlueButton API.
 *
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization.
 */
$course_plugin = 'openmeetings'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';
$plugin = \OpenMeetingsPlugin::create();
$tool_name = $plugin->get_lang('Videoconference');
$tpl = new Template($tool_name);

$om = new Chamilo\Plugin\OpenMeetings\OpenMeetings();
$action = isset($_GET['action']) ? $_GET['action'] : null;
$teacher = $om->isTeacher();

api_protect_course_script(true);
$message = null;

if ($teacher) {
    switch ($action) {
        case 'add_to_calendar':
            $course_info = api_get_course_info();
            $agenda = new Agenda('course');

            $id = intval($_GET['id']);
            $title = sprintf(get_lang('VideoConferenceXCourseX'), $id, $course_info['name']);
            $content = Display::url(get_lang('GoToTheVideoConference'), $_GET['url']);

            $event_id = $agenda->addEvent(
                $_REQUEST['start'],
                null,
                'true',
                $title,
                $content,
                ['everyone']
            );
            if (!empty($event_id)) {
                $message = Display::return_message(get_lang('VideoConferenceAddedToTheCalendar'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }
            break;
        case 'copy_record_to_link_tool':
            $result = $om->copy_record_to_link_tool($_GET['id'], $_GET['record_id']);
            if ($result) {
                $message = Display::return_message(get_lang('VideoConferenceAddedToTheLinkTool'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }
            break;
        case 'delete_record':
            /*$result = $om->delete_record($_GET['id']);
            if ($result) {
                $message = Display::return_message(get_lang('Deleted'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }*/
            break;
        case 'delete':
            $om->deleteMeeting($_GET['id']);
            unset($om);
            $message = Display::return_message(get_lang('MeetingDeleted').'<br />'.get_lang('MeetingDeletedComment'), 'success', false);
            header('Location: '.api_get_self());
            exit;
            break;
        case 'end':
            $om->endMeeting($_GET['id']);
            $message = Display::return_message(get_lang('MeetingClosed').'<br />'.get_lang('MeetingClosedComment'), 'success', false);
            break;
        case 'publish':
            // Not implemented yet
            //$result = $om->publish_meeting($_GET['id']);
            break;
        case 'unpublish':
            // Not implemented yet
            //$result = $om->unpublish_meeting($_GET['id']);
            break;
        default:
            break;
    }
}

$meetings = $om->getCourseMeetings();
$openMeeting = false;
$users_online = 0;

if (!empty($meetings)) {
    $meetings = array_reverse($meetings);
    foreach ($meetings as $meeting) {
        if ($meeting['status'] == 1) {
            $openMeeting = true;
        }
    }
    $users_online = $meetings->participantCount;
}

$status = $om->isServerRunning();
$show_join_button = false;
if ($openMeeting || $teacher) {
    $show_join_button = true;
}

$tpl->assign('allow_to_edit', $teacher);
$tpl->assign('meetings', $meetings);
$conference_url = api_get_path(WEB_PLUGIN_PATH).'openmeetings/start.php?launch=1&'.api_get_cidreq();
$tpl->assign('conference_url', $conference_url);
$tpl->assign('users_online', $users_online);
$tpl->assign('openmeetings_status', $status);
$tpl->assign('show_join_button', $show_join_button);
$tpl->assign('message', $message);
$listing_tpl = 'openmeetings/listing.tpl';
$content = $tpl->fetch($listing_tpl);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
