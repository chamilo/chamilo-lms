<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin.ticket
 */

/* Tables names constants */
define('PLUGIN_NAME', 'ticket');
define('TABLE_TICKET_ASSIGNED_LOG', 'plugin_ticket_assigned_log');
define('TABLE_TICKET_CATEGORY', 'plugin_ticket_category');
define('TABLE_TICKET_MESSAGE', 'plugin_ticket_message');
define('TABLE_TICKET_PRIORITY', 'plugin_ticket_priority');
define('TABLE_TICKET_PROJECT', 'plugin_ticket_project');
define('TABLE_TICKET_STATUS', 'plugin_ticket_status');
define('TABLE_TICKET_TICKET', 'plugin_ticket_ticket');
define('TABLE_TICKET_CATEGORY_REL_USER', 'plugin_ticket_category_rel_user');
define('TABLE_TICKET_MESSAGE_ATTACHMENTS', 'plugin_ticket_message_attachments');

require_once __DIR__ . '/../../main/inc/global.inc.php';

require_once api_get_path(SYS_PLUGIN_PATH) . PLUGIN_NAME . '/src/ticket_plugin.class.php';
require_once api_get_path(SYS_PLUGIN_PATH) . PLUGIN_NAME . '/src/ticket.class.php';
