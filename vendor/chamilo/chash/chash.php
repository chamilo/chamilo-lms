<?php
/**
 * Command-line tool to do things more swiftly in Chamilo.
 * To add support for a new command see the Console Component read:
 *
 * https://speakerdeck.com/hhamon/symfony-extending-the-console-component
 * http://symfony.com/doc/2.0/components/console/introduction.html
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @author Julio Montoya <gugli100@gmail.com>
 * @version 2.0
 * @license This script is provided under the terms of the GNU/GPLv3+ license
 */

/**
 * Security check: do not allow any other calling method than command-line
 */

if (PHP_SAPI != 'cli') {
    die("Chash cannot be called by any other method than the command line.");
}

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;

$helpers = array(
    'configuration' => new Chash\Helpers\ConfigurationHelper()
);

$application = new Application('Chamilo Command Line Interface', '1.0');

$helperSet = $application->getHelperSet();
foreach ($helpers as $name => $helper) {
    $helperSet->set($helper, $name);
}

$application->addCommands(array(
    new Chash\Command\Database\RunSQLCommand(),
    new Chash\Command\Database\DumpCommand(),
    new Chash\Command\Database\RestoreCommand(),
    new Chash\Command\Database\SQLCountCommand(),
    new Chash\Command\Database\FullBackupCommand(),
    new Chash\Command\Database\DropDatabaseCommand(),
    new Chash\Command\Files\CleanTempFolderCommand(),
    new Chash\Command\Files\CleanConfigFiles(),
    new Chash\Command\Translation\ExportLanguageCommand(),
    new Chash\Command\Translation\ImportLanguageCommand()
));

$application->run();

//Interactive shell
//$shell = new Console\Shell($application);
//$shell->run();

/**
 * Initialization: find the local Chamilo configuration file or thow error
 */
if (!$config_file = _chash_find_config_file()) {
    die(_t(
        "Couldn't find config file. Please either give the path to the Chamilo installation you want to act on, through the -c option, or 'cd' into a valid Chamilo installation directory"
    ));
}

/**
 * Helper functions
 */
/**
 * Shows the usage documentation (all possible commands and the general syntax
 */
function _chash_usage()
{
    echo "\n";
    echo _t(
        "ChaSh goes for \"Chamilo Shell\".\nIt allows you to execute common administrative operations on a Chamilo LMS installation (1.9 or higher) from the command line."
    )."\n";
    echo _t(
        "ChaSh is developed by BeezNest, the Chamilo specialist corporation. See http://www.beeznest.com/ for contact details."
    )."\n";
    echo _t(
        'You can call chash.php with a series of commands. Each command has its own parameters. To run chash.php, you can either call it from inside a Chamilo directory (it will then find its way on its own) or from any other directory giving the path to the configuration file with --conf=/path/to/configuration.php'
    )."\n\n";
    echo _t('  Usage: php5 chash.php [command] [options]')."\n\n";
    // -- Commands explanation --
    echo _t('Available commands:')."\n";
    echo _t("  sql_cli\t\tEnters to the SQL command line")."\n";
    echo _t("  sql_dump\t\tOutputs a dump of the database")."\n";
    echo _t("  sql_restore\t\tInserts a database dump into the active database")."\n";
    echo _t("  sql_count\t\tOutputs a report about the number of rows in a table")."\n";
    echo _t("  full_backup\t\tGenerates a .tgz from the Chamilo files and database")."\n";
    echo _t("  clean_archives\tCleans the archives directory")."\n";
    echo _t("  drop_databases\tDrops all databases from the current Chamilo install")."\n";
    echo _t("  export_language_package\tGenerates a .tgz file with all the translations for a wanted language")."\n";
    echo _t("  import_language_package\tImports .tgz file previously created")."\n";
    echo "\n";
    echo _t("Available options:")."\n";
    echo _t("  --conf=\tIndicates to chash where to find the configuration file of Chamilo.")."\n";
    echo "\n";
}


/**
 * Find the complete path to the Chamilo configuration file
 * @return string Path to the configuration file
 */
function _chash_find_config_file()
{
    global $argc, $argv;
    $found = false;
    if ($argc > 1) {
        $find = '--conf=';
        foreach ($argv as $arg) {
            if (substr($arg, 0, 7) == $find) {
                if (is_file(substr($arg, 7))) {
                    $found = substr($arg, 7);
                    break;
                }
                if (substr($arg, -1, 1) == '/') {
                    $arg = substr($arg, 0, -1);
                }
                if (is_file(substr($arg, 7).'/configuration.php')) {
                    $found = substr($arg, 7).'/configuration.php';
                    break;
                }
                if (is_file(substr($arg, 7).'/main/inc/conf/configuration.php')) {
                    $found = substr($arg, 7).'/main/inc/conf/configuration.php';
                    break;
                }
            }
        }
    }
    if (!$found) {
        $dir = getcwd();
        for ($i = 0; $i < 10; $i++) {
            $dir = realpath($dir);
            if (is_file($dir.'/configuration.php')) {
                $found = $dir.'/configuration.php';
                break;
            } elseif (is_file($dir.'/conf/configuration.php')) {
                $found = $dir.'/conf/configuration.php';
                break;
            } elseif (is_file($dir.'/inc/conf/configuration.php')) {
                $found = $dir.'/inc/conf/configuration.php';
                break;
            } elseif (is_file($dir.'/main/inc/conf/configuration.php')) {
                $found = $dir.'/main/inc/conf/configuration.php';
                break;
            } else {
                $dir = $dir.'/../';
            }
        }
    }
    return $found;
}

function _t($var)
{
    return $var;
}