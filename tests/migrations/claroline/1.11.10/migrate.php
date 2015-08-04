<?php
/**
 * This script executes the migration from a Claroline 1.11.10 system to a 
 * Chamilo LMS 1.10.0 system.
 * This should be launched on the command line to avoid maximum connection
 * times defined by the browser or the web server.
 * Configuration of the source should be set in the config.php file.
 * Configuration of Chamilo is taken from the current folder (current
 * already-installed Chamilo portal).
 */
require_once __DIR__.'/config.php';
if (!isset($sourceHost)) {
    die ('Please define the source and other parameters in config.php'.PHP_EOL);
}

require_once __DIR__.'/../../../../main/inc/global.inc.php';
require_once __DIR__.'/migrate.class.php';

echo "Working" . PHP_EOL;
$migrate = new Migrate();

echo "Migrating users..." . PHP_EOL;
$count = $migrate->migrateUsers();
echo $count . " users migrated." . PHP_EOL;

echo "Done";
