<?php
/* For licensing terms, see /license.txt */

/* Tables names constants */
define('PLUGIN_NAME', 'ticket');
define('TABLE_SUPPORT_ASSIGNED_LOG', 'tck_assigned_log');
define('TABLE_SUPPORT_CATEGORY', 'tck_category');
define('TABLE_SUPPORT_MESSAGE', 'tck_message');
define('TABLE_SUPPORT_PRIORITY', 'tck_priority');
define('TABLE_SUPPORT_PROJECT', 'tck_project');
define('TABLE_SUPPORT_STATUS', 'tck_status');
define('TABLE_SUPPORT_TICKET', 'tck_ticket');
define('TABLE_SUPPORT_MESSAGE_ATTACHMENTS', 'tck_message_attachments');

/* Ticket status constants */
define('NEWTCK', 'NAT'); // New ticket unassigned responsible
define('PENDING', 'PND'); // User waiting answer
define('UNCONFIRMED', 'XCF'); // Waiting for user response
define('CLOSE', 'CLS'); // Close ticket
define('REENVIADO', 'REE'); // @todo delete option. This is a location of USIL

/* Ticket priority constants */
define('NORMAL', 'NRM');
define('HIGH', 'ALT');
define('LOW', 'LOW');

/* Ticket source constants */
define('SRC_EMAIL', 'MAI');
define('SRC_PHONE', 'TEL');
define('SRC_PRESC', 'PRE');

/* Ticket category constants */
define('CAT_DOCU', 'DOC');
define('CAT_FORO', 'FOR');
define('CAT_ANNU', 'ANN');

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'plugin.class.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
include_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

require_once api_get_path(PLUGIN_PATH).PLUGIN_NAME.'/lib/ticket.class.php';
require_once api_get_path(PLUGIN_PATH).PLUGIN_NAME.'/lib/ticket_plugin.class.php';
require_once api_get_path(PLUGIN_PATH).PLUGIN_NAME.'/s/ticket.class.php';