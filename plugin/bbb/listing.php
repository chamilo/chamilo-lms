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


$bbb = new bbb();

$action = isset($_GET['action']) ? $_GET['action'] : null;
switch ($action) {
    case 'end':
        $bbb->end_meeting($_GET['id']);
        break;
    case 'save_to_documents':
        $bbb->save_recording_to_document($_GET['id']);
        break;    
    case 'publish':
        $result = $bbb->publish_meeting($_GET['id']);
        break;
    case 'unpublish':
        $result = $bbb->unpublish_meeting($_GET['id']);
        break;
}

$meetings = $bbb->get_course_meetings();
$users_online = $bbb->get_users_online_in_current_room();



$tpl = new Template($tool_name);

$tpl->assign('meetings', $meetings);
$conference_url = api_get_path(WEB_PLUGIN_PATH).'bbb/start.php?'.api_get_cidreq();
$tpl->assign('conference_url', $conference_url);
$tpl->assign('users_online', $users_online);

$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$listing_tpl = 'bbb/listing.tpl';
$content = $tpl->fetch($listing_tpl);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
