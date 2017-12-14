<?php
/* For licensing terms, see /license.txt */

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Helper\HelperSet;
use Doctrine\DBAL\Types\Type;

/**
 * Very useful script in order to create a Migration file based in the
 * current differences of the database:
 *
 * php bin/doctrine.php migrations:diff
 *
 * This script also show doctrine basic commands:
 * - Create schema
 * - Drop schema
 * - Update schema,
 * etc
 *
 **/

(@include_once __DIR__.'/../vendor/autoload.php') || @include_once __DIR__.'/../../../autoload.php';

$directories = array(getcwd(), getcwd().DIRECTORY_SEPARATOR.'config');

$configFile = null;
foreach ($directories as $directory) {
    $configFile = $directory.DIRECTORY_SEPARATOR.'cli-config.php';

    if (file_exists($configFile)) {
        break;
    }
}

if (!file_exists($configFile)) {
    ConsoleRunner::printCliConfigTemplate();
    exit(1);
}

if (!is_readable($configFile)) {
    echo 'Configuration file ['.$configFile.'] does not have read permission.'."\n";
    exit(1);
}

Type::overrideType(
    Type::DATETIME,
    Database::getUTCDateTimeTypeClass()
);

/*Type::addType(
    'json',
    'Sonata\Doctrine\Types\JsonType'
);*/

$commands = array(
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\LatestCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
    new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand()
);

$helperSet = require $configFile;

if (!($helperSet instanceof HelperSet)) {
    foreach ($GLOBALS as $helperSetCandidate) {
        if ($helperSetCandidate instanceof HelperSet) {
            $helperSet = $helperSetCandidate;
            break;
        }
    }
}

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet, $commands);
