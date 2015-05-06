<?php
/* For licensing terms, see /license.txt */
require_once '../../../global.inc.php';

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$roomName = isset($_GET['room']) ? $_GET['room'] : null;

$room = VideoChat::getChatRoomByName($roomName);

if ($room === false) {
    Header::location(api_get_path(WEB_PATH));
}

$isSender = $room['from_user'] == api_get_user_id();
$isReceiver = $room['to_user'] == api_get_user_id();

if (!$isSender && !$isReceiver) {
    Header::location(api_get_path(WEB_PATH));
}

if ($isSender) {
    $chatUser = api_get_user_info($room['to_user']);
} elseif ($isReceiver) {
    $chatUser = api_get_user_info($room['from_user']);
}

$htmlHeadXtra[] = '<script type="text/javascript" src="'
    . api_get_path(WEB_PATH) . 'web/assets/simplewebrtc/latest.js'
    . '"></script>' . "\n";

$template = new Template();
$template->assign('room_name', $room['room_name']);
$template->assign('chat_user', $chatUser);

$content = $template->fetch('default/chat/video.tpl');

$template->assign('header', $room['room_name']);
$template->assign('content', $content);
$template->assign('message', Display::return_message(get_lang('BroswerDoesNotSupportWebRTC'), 'warning'));
$template->display_one_col_template();
