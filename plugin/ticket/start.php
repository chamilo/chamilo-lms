<?php
/* For licensing terms, see /license.txt */

/**
 * This script initiates a ticket management system session
 * @package chamilo.plugin.ticket
 */
$course_plugin = 'ticket'; //needed in order to load the plugin lang variables
require_once dirname(__FILE__).'/config.php';
$tool_name = get_lang('Ticket');
$tpl = new Template($tool_name);

$tpl->assign('message', $message);
$tpl->display_one_col_template();
