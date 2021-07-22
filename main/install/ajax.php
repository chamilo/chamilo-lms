<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo installation
 * AJAX requests for the installation.
 *
 * @package chamilo.install
 */
ini_set('display_errors', '1');
ini_set('log_errors', '1');
error_reporting(-1);

require_once __DIR__.'/../../vendor/autoload.php';

define('SYSTEM_INSTALLATION', 1);
define('INSTALL_TYPE_UPDATE', 'update');
define('FORM_FIELD_DISPLAY_LENGTH', 40);
define('DATABASE_FORM_FIELD_DISPLAY_LENGTH', 25);
define('MAX_FORM_FIELD_LENGTH', 80);

// Including necessary libraries.
require_once '../inc/lib/api.lib.php';

session_start();

require_once api_get_path(LIBRARY_PATH).'database.constants.inc.php';
require_once 'install.lib.php';

// A protection measure for already installed systems.
if (isAlreadyInstalledSystem()) {
    // The system has already been installed, so block re-installation.
    echo "Chamilo has already been installed";
    exit;
}

$action = isset($_POST['a']) ? $_POST['a'] : null;

$dbHost = isset($_POST['db_host']) ? $_POST['db_host'] : 'localhost';
$dbUsername = isset($_POST['db_username']) ? $_POST['db_username'] : 'root';
$dbPass = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';
$dbName = isset($_POST['db_name']) ? $_POST['db_name'] : 'chamilo';
$installType = isset($_POST['install_type']) ? $_POST['install_type'] : 'new';

if ($installType === 'new') {
    $dbName = null;
}

$dbPort = isset($_POST['db_port']) ? (int) $_POST['db_port'] : 3306;

$manager = connectToDatabase($dbHost, $dbUsername, $dbPass, $dbName, $dbPort);

$db_prefix = api_get_configuration_value('db_prefix') ? api_get_configuration_value('db_prefix') : 'chamilo_';
$db_c_prefix = api_get_configuration_value('table_prefix') ? api_get_configuration_value('table_prefix') : 'crs_';

switch ($action) {
    case 'check_crs_tables':
        if (empty($dbName)) {
            echo 0;

            break;
        }

        $countOfTables = $manager
            ->getConnection()
            ->executeQuery("SHOW TABLES LIKE '$db_c_prefix$db_prefix%'")
            ->rowCount();

        echo $countOfTables;
        break;
    case 'remove_crs_tables':
        $statement = $manager
            ->getConnection()
            ->executeQuery("SHOW TABLES LIKE '$db_c_prefix$db_prefix%'");

        while ($table = $statement->fetch(PDO::FETCH_NUM)) {
            $manager->getConnection()->executeQuery("DROP TABLE {$table[0]}");
        }

        break;
    default:
        break;
}
