<?php
/**
 * This script initiates a videoconference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization
 */

$course_plugin = 'bbb';

require_once '../../main/inc/global.inc.php';
require_once 'bbb.lib.php';
require_once 'bbb_api.php';

$bbb = new bbb();

$action = isset($_GET['action']) ? $_GET['action'] : null;

switch ($action) {
    case 'copy_record_to_link_tool':
        $result = $bbb->copy_record_to_link_tool($_GET['id'], $_GET['record_id']);        
        if ($result) {
            $message = Display::return_message(get_lang('Copied'), 'success');
        } else {
            $message = Display::return_message(get_lang('Error'), 'error');
        }
        break;
    case 'delete_recording':
        //$bbb->delete_record($_GET['id']);     
        break;
    case 'end':
        $bbb->end_meeting($_GET['id']);
        $message = Display::return_message(get_lang('MeetingClosed'), 'success');
        break;    
    case 'publish':
        //$result = $bbb->publish_meeting($_GET['id']);
        break;
    case 'unpublish':
        //$result = $bbb->unpublish_meeting($_GET['id']);
        break;
}

$meetings       = $bbb->get_course_meetings();
$users_online   = $bbb->get_users_online_in_current_room();
$status         = $bbb->is_server_running();
$status         = false;

$tool_name = get_lang('Videoconference');

$tpl = new Template($tool_name);

$tpl->assign('meetings', $meetings);
$conference_url = api_get_path(WEB_PLUGIN_PATH).'bbb/start.php?launch=1&'.api_get_cidreq();
$tpl->assign('conference_url', $conference_url);
$tpl->assign('users_online', $users_online);
$tpl->assign('bbb_status', $status);

$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$listing_tpl = 'bbb/listing.tpl';
$content = $tpl->fetch($listing_tpl);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
