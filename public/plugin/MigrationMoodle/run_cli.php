<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\MigrationMoodle\Script\BaseScript;
use Chamilo\PluginBundle\MigrationMoodle\Task\BaseTask;

$cidReset = true;

ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);

require_once __DIR__.'/../../main/inc/global.inc.php';

if (PHP_SAPI !== 'cli') {
    echo 'Run on CLI.'.PHP_EOL;
    exit;
}

$outputBuffering = false;

$plugin = MigrationMoodlePlugin::create();

if (!$plugin->isEnabled()) {
    echo 'MigrationMoodle plugin is disabled.'.PHP_EOL;
    exit(1);
}

if (!$plugin->hasRequiredDatabaseConfiguration()) {
    echo 'Missing Moodle database configuration.'.PHP_EOL;
    exit(1);
}

if ('' === $plugin->getMoodledataPath()) {
    echo 'Warning: moodledata path is empty. File-related tasks may fail.'.PHP_EOL;
}

foreach ($plugin->getCliTaskNames() as $i => $taskName) {
    $taskClass = api_underscore_to_camel_case($taskName).'Task';
    $taskClass = 'Chamilo\\PluginBundle\\MigrationMoodle\\Task\\'.$taskClass;

    echo PHP_EOL.'['.date(DateTime::ATOM).'] '.($i + 1).': ';

    if ($plugin->isTaskDone($taskName)) {
        echo "Already done \"$taskClass\"".PHP_EOL;
        continue;
    }

    if (!class_exists($taskClass)) {
        echo "Task class not found \"$taskClass\"".PHP_EOL;
        continue;
    }

    echo "Executing \"$taskClass\".".PHP_EOL;

    try {
        /** @var BaseTask $task */
        $task = new $taskClass();
        $task->execute();
    } catch (Throwable $throwable) {
        echo "Task failed \"$taskClass\": ".$throwable->getMessage().PHP_EOL;
        exit(1);
    }

    echo '['.date(DateTime::ATOM)."] End \"$taskClass\"".PHP_EOL;
}

foreach ($plugin->getCliScriptNames() as $i => $scriptName) {
    $scriptClass = api_underscore_to_camel_case($scriptName).'Script';
    $scriptClass = 'Chamilo\\PluginBundle\\MigrationMoodle\\Script\\'.$scriptClass;

    echo PHP_EOL.'['.date(DateTime::ATOM).'] '.($i + 1).': ';

    if ($plugin->isTaskDone($scriptName)) {
        echo "Already done \"$scriptClass\"".PHP_EOL;
        continue;
    }

    if (!class_exists($scriptClass)) {
        echo "Script class not found \"$scriptClass\"".PHP_EOL;
        continue;
    }

    echo "Executing \"$scriptClass\".".PHP_EOL;

    try {
        /** @var BaseScript $script */
        $script = new $scriptClass();
        $script->run();
    } catch (Throwable $throwable) {
        echo "Script failed \"$scriptClass\": ".$throwable->getMessage().PHP_EOL;
        exit(1);
    }

    echo '['.date(DateTime::ATOM)."] End \"$scriptClass\"".PHP_EOL;
}
