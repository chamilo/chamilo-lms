<?php
/* For licensing terms, see /license.txt */

/**
 * Script needed to execute bin/doctrine.php in the command line
 * in order to:.
 *
 * - Generate migrations
 * - Create schema
 * - Update schema
 * - Validate schema
 * - Etc
 */
use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once __DIR__.'/vendor/autoload.php';
//require_once __DIR__.'/main/inc/lib/api.lib.php';
$configurationFile = __DIR__.'/app/config/configuration.php';

if (!is_file($configurationFile)) {
    echo "File does not exists: $configurationFile";
    exit();
}

require_once __DIR__.'/main/inc/global.inc.php';
require_once $configurationFile;

$database = new \Database();
$dbParams = [
    'driver' => 'pdo_mysql',
    'host' => $_configuration['db_host'],
    'user' => $_configuration['db_user'],
    'password' => $_configuration['db_password'],
    'dbname' => $_configuration['main_database'],
];

$database->connect($dbParams, realpath(__DIR__).'/', realpath(__DIR__).'/');
$entityManager = $database::getManager();

$helperSet = ConsoleRunner::createHelperSet($entityManager);
$dialogHelper = new Symfony\Component\Console\Helper\QuestionHelper();
$helperSet->set($dialogHelper);

return $helperSet;
