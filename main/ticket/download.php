<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin.ticket
 */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
$user_id = api_get_user_id();
if (!isset($_GET['file']) || !isset($_GET['title']) || !isset($_GET['ticket_id'])) {
    api_not_allowed();
}

if (!api_is_platform_admin()) {
    $ticket_id = intval($_GET['ticket_id']);
    $table_support_messages = Database::get_main_table(TABLE_TICKET_MESSAGE);
    $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
    $table_support_message_attachments = Database::get_main_table(TABLE_TICKET_MESSAGE_ATTACHMENTS);
    $sql = "SELECT DISTINCT  ticket.request_user
          FROM  $table_support_tickets ticket,
                $table_support_messages message,
                $table_support_message_attachments attch
            WHERE ticket.ticket_id = message.ticket_id
            AND attch.message_id = message.message_id
            AND ticket.ticket_id = $ticket_id";
    $rs = Database::query($sql);
    $row_users = Database::fetch_array($rs, 'ASSOC');
    $user_request_id = $row_users['request_user'];
    if (intval($user_request_id) != $user_id) {
        api_not_allowed();
    }
}

// @todo replace by Security::check_abs_path()?
$file_url = $_GET['file'];
$file_url = str_replace('///', '&', $file_url);
$file_url = str_replace(' ', '+', $file_url);
$file_url = str_replace('/..', '', $file_url);
$file_url = Database::escape_string($file_url);
$title = $_GET['title'];
$path_attachment = api_get_path(SYS_ARCHIVE_PATH);
$path_message_attach = $path_attachment . 'plugin_ticket_messageattch/';
$full_file_name = $path_message_attach . $file_url;
if (Security::check_abs_path($full_file_name, $path_message_attach)) {
    DocumentManager::file_send_for_download($full_file_name, true, $title);
}

exit;
