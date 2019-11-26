<?php
/* For licensing terms, see /license.txt */

/**
 * This file is responsible for  passing requested file attachments from messages
 * Html files are parsed to fix a few problems with URLs,
 * but this code will hopefully be replaced soon by an Apache URL
 * rewrite mechanism.
 *
 * @package chamilo.messages
 */
session_cache_limiter('public');

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

// IMPORTANT to avoid caching of documents
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Cache-Control: public');
header('Pragma: no-cache');

$messageId = isset($_GET['message_id']) ? $_GET['message_id'] : 0;
$attachmentId = isset($_GET['attachment_id']) ? $_GET['attachment_id'] : 0;

$messageInfo = MessageManager::get_message_by_id($messageId);
$attachmentInfo = MessageManager::getAttachment($attachmentId);

if (empty($messageInfo) || empty($attachmentInfo)) {
    api_not_allowed();
}

// Attachment belongs to the message?
if ($messageInfo['id'] != $attachmentInfo['message_id']) {
    api_not_allowed();
}

// Do not process group items
if (!empty($messageInfo['group_id'])) {
    api_not_allowed();
}

// Only process wall messages
if (!in_array($messageInfo['msg_status'], [MESSAGE_STATUS_WALL, MESSAGE_STATUS_WALL_POST, MESSAGE_STATUS_PROMOTED])) {
    api_not_allowed();
}

$dir = UserManager::getUserPathById($messageInfo['user_sender_id'], 'system');
if (empty($dir)) {
    api_not_allowed();
}

$file = $dir.'message_attachments/'.$attachmentInfo['path'];
$title = api_replace_dangerous_char($attachmentInfo['filename']);

if (Security::check_abs_path($file, $dir.'message_attachments/')) {
    // launch event
    Event::event_download($file);
    $result = DocumentManager::file_send_for_download(
        $file,
        false,
        $title
    );
    if ($result === false) {
        api_not_allowed(true);
    }
}
exit;
