<?php
/* For licensing terms, see /license.txt */

/**
 * Tests database connection.
 *
 * @package vchamilo
 *
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

// Loading configuration.
require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script();

global $_configuration;

$plugin = VChamiloPlugin::create();

// Retrieve parameters for database connection test.
$dbParams = [];
$dbParams['db_host'] = $_REQUEST['vdbhost'];
$dbParams['db_user'] = $_REQUEST['vdblogin'];
$dbParams['db_password'] = $_REQUEST['vdbpass'];
$dbParams['root_sys'] = api_get_path(SYS_PATH);

$dbParams = [
    'driver' => 'pdo_mysql',
    'host' => $_REQUEST['vdbhost'],
    'user' => $_REQUEST['vdblogin'],
    'password' => $_REQUEST['vdbpass'],
    //'dbname' => isset($_configuration['main_database']) ? $_configuration['main_database'] : '',
    // Only relevant for pdo_sqlite, specifies the path to the SQLite database.
    //'path' => isset($_configuration['db_path']) ? $_configuration['db_path'] : '',
    // Only relevant for pdo_mysql, pdo_pgsql, and pdo_oci/oci8,
    //'port' => isset($_configuration['db_port']) ? $_configuration['db_port'] : '',
];

try {
    $database = new \Database();
    $connection = $database->connect(
        $dbParams,
        $_configuration['root_sys'],
        $_configuration['root_sys'],
        true
    );

    $list = $connection->getSchemaManager()->listDatabases();
    echo $plugin->get_lang('connectionok');
} catch (Exception $e) {
    echo $plugin->get_lang('badconnection');
    exit();
}
