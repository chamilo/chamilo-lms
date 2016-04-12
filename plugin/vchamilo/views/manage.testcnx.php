<?php
/**
 * Tests database connection.
 *
 * @package vchamilo
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

// Loading configuration.
require_once('../../../main/inc/global.inc.php');
require_once($_configuration['root_sys'].'/local/classes/mootochamlib.php');
require_once($_configuration['root_sys'].'/local/classes/database.class.php');
require_once(api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib/vchamilo_plugin.class.php');
require_once(api_get_path(SYS_PLUGIN_PATH).'vchamilo/lib.php');

$plugininstance = VChamiloPlugin::create();

// Retrieve parameters for database connection test.
$database = array();
$database['db_host']     = $_REQUEST['vdbhost'];
$database['db_user']     = $_REQUEST['vdblogin'];
$database['db_password'] = $_REQUEST['vdbpass'];

// Works, but need to improve the style...
if (vchamilo_boot_connection($database, false)) {
    echo($plugininstance->get_lang('connectionok'));
} else {
    echo($plugininstance->get_lang('badconnection'));
}
