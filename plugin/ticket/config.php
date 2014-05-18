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
define('TABLE_TICKET_MESSAGE_ATTACHMENTS', 'plugin_ticket_message_attachments');

/* Ticket status constants */
define('NEWTCK', 'NAT'); // New ticket unassigned responsible
define('PENDING', 'PND'); // User waiting answer
define('UNCONFIRMED', 'XCF'); // Waiting for user response
define('CLOSE', 'CLS'); // Close ticket
define('REENVIADO', 'REE'); // @todo delete option. This is a location of USIL

/* Ticket priority constants */
define('NORMAL', 'NRM');
define('HIGH', 'HGH');
define('LOW', 'LOW');

/* Ticket source constants */
define('SRC_EMAIL', 'MAI');
define('SRC_PHONE', 'TEL');
define('SRC_PRESC', 'PRE');
define('SRC_PLATFORM', 'PLA');

/* Ticket category constants */
define('CAT_DOCU', 'DOC');
define('CAT_FORO', 'FOR');
define('CAT_ANNU', 'ANN');

require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once api_get_path(LIBRARY_PATH) . 'course.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php';

require_once api_get_path(PLUGIN_PATH) . PLUGIN_NAME . '/src/ticket_plugin.class.php';
require_once api_get_path(PLUGIN_PATH) . PLUGIN_NAME . '/src/ticket.class.php';
