<?php
/* For licensing terms, see /license.txt */

/**
 * This script is included by main/admin/settings.lib.php and generally
 * includes things to execute in the main database (settings_current table)
 * @package chamilo.plugin.ticket
 */

require_once dirname(__FILE__).'/config.php';
TicketPlugin::create()->install();
