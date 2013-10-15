<?php
/* For licensing terms, see /license.txt */

/* bbb parameters that will be registered in the course settings */

define("PLUGIN_NAME", "ticket");
define("TABLE_SUPPORT_ASSIGNED_LOG", "tck_assigned_log");
define("TABLE_SUPPORT_CATEGORY", "tck_category");
define("TABLE_SUPPORT_MESSAGE", "tck_message");
define("TABLE_SUPPORT_PRIORITY", "tck_priority");
define("TABLE_SUPPORT_PROJECT", "tck_project");
define("TABLE_SUPPORT_STATUS", "tck_status");
define("TABLE_SUPPORT_TICKET", "tck_ticket");
define("TABLE_SUPPORT_MESSAGE_ATTACHMENTS", "tck_message_attachments");

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'plugin.class.php';

require_once api_get_path(PLUGIN_PATH).PLUGIN_NAME.'/lib/tck.lib.php';
require_once api_get_path(PLUGIN_PATH).PLUGIN_NAME.'/lib/tck_api.php';
require_once api_get_path(PLUGIN_PATH).PLUGIN_NAME.'/lib/tck_plugin.class.php';