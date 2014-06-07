<?php
/**
 * This script initiates a videoconference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization
 */

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
require_once dirname(__FILE__).'/config.php';
$plugin = BBBPlugin::create();
$tool_name = $plugin->get_lang('Videoconference');
$tpl = new Template($tool_name);

$bbb = new bbb();
$action = isset($_GET['action']) ? $_GET['action'] : null;

$teacher = $bbb->is_teacher();

api_protect_course_script(true);
$message = null;

if ($teacher) {
    switch ($action) {
        case 'add_to_calendar':
            $course_info = api_get_course_info();
            $agenda = new Agenda();
            $agenda->type = 'course';

            $id = intval($_GET['id']);
            $title = sprintf(get_lang('VideoConferenceXCourseX'), $id, $course_info['name']);
            $content = Display::url(get_lang('GoToTheVideoConference'), $_GET['url']);

            $event_id = $agenda->add_event(
                $_REQUEST['start'],
                null,
                'true',
                $title,
                $content,
                array('everyone')
            );
            if (!empty($event_id)) {
                $message = Display::return_message(get_lang('VideoConferenceAddedToTheCalendar'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }
            break;
        case 'copy_record_to_link_tool':
            $result = $bbb->copy_record_to_link_tool($_GET['id'], $_GET['record_id']);
            if ($result) {
                $message = Display::return_message(get_lang('VideoConferenceAddedToTheLinkTool'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }
            break;
        case 'delete_record':
            $bbb->delete_record($_GET['id']);
            if ($result) {
                $message = Display::return_message(get_lang('Deleted'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }
            break;
        case 'end':
            $bbb->end_meeting($_GET['id']);
            $message = Display::return_message(
                get_lang('MeetingClosed') . '<br />' . get_lang(
                    'MeetingClosedComment'
                ),
                'success',
                false
            );

            if (file_exists(__DIR__ . '/config.vm.php')) {
                require __DIR__ . '/../../vendor/autoload.php';

                require __DIR__ . '/lib/vm/AbstractVM.php';
                require __DIR__ . '/lib/vm/VMInterface.php';
                require __DIR__ . '/lib/vm/DigitalOceanVM.php';
                require __DIR__ . '/lib/VM.php';

                $config = require __DIR__ . '/config.vm.php';

                $vm = new VM($config);
                $vm->resizeToMinLimit();
            }

            break;
        case 'publish':
            //$result = $bbb->publish_meeting($_GET['id']);
            break;
        case 'unpublish':
            //$result = $bbb->unpublish_meeting($_GET['id']);
            break;
        default:
            break;
    }
}

$meetings = $bbb->get_course_meetings();
if (!empty($meetings)) {
    $meetings = array_reverse($meetings);
}
$users_online   = $bbb->get_users_online_in_current_room();
$status         = $bbb->is_server_running();
$meeting_exists = $bbb->meeting_exists(api_get_course_id().'-'.api_get_session_id());
$show_join_button = false;
if ($meeting_exists || $teacher) {
    $show_join_button = true;
}

$tpl->assign('allow_to_edit', $teacher);
$tpl->assign('meetings', $meetings);
$conference_url = api_get_path(WEB_PLUGIN_PATH).'bbb/start.php?launch=1&'.api_get_cidreq();
$tpl->assign('conference_url', $conference_url);
$tpl->assign('users_online', $users_online);
$tpl->assign('bbb_status', $status);
$tpl->assign('show_join_button', $show_join_button);

//$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$listing_tpl = 'bbb/listing.tpl';
$content = $tpl->fetch($listing_tpl);
$tpl->assign('content', $content);$tpl->display_one_col_template();
