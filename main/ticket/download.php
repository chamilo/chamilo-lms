<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin.ticket
 */
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
$user_id = api_get_user_id();

if (!isset($_GET['id']) || !isset($_GET['ticket_id'])) {
    api_not_allowed(true);
}

$ticket_id = (int) $_GET['ticket_id'];
$ticketInfo = TicketManager::get_ticket_detail_by_id($ticket_id);
if (empty($ticketInfo)) {
    api_not_allowed(true);
}
$messageAttachment = TicketManager::getTicketMessageAttachment($_GET['id']);
if (empty($messageAttachment)) {
    api_not_allowed(true);
}

if (!api_is_platform_admin()) {
    $table_support_messages = Database::get_main_table(TABLE_TICKET_MESSAGE);
    $table_support_tickets = Database::get_main_table(TABLE_TICKET_TICKET);
    $table_support_message_attachments = Database::get_main_table(TABLE_TICKET_MESSAGE_ATTACHMENTS);
    $sql = "SELECT DISTINCT  ticket.request_user
            FROM $table_support_tickets ticket,
                $table_support_messages message,
                $table_support_message_attachments attch
            WHERE ticket.ticket_id = message.ticket_id
            AND attch.message_id = message.message_id
            AND ticket.ticket_id = $ticket_id";
    $rs = Database::query($sql);
    $row_users = Database::fetch_array($rs, 'ASSOC');
    $user_request_id = $row_users['request_user'];
    if (intval($user_request_id) != $user_id) {
        api_not_allowed(true);
    }
}

api_download_uploaded_file(
    'ticket_attachment',
    $ticket_id,
    $messageAttachment->getPath(),
    $messageAttachment->getFilename()
);
exit;
