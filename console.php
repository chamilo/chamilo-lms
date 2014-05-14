#!/usr/bin/env php
<?php
/* For licensing terms, see /license.txt */

set_time_limit(0);

if (PHP_SAPI != 'cli') {
    die("Cannot be called by any other method than the command line.");
}
require_once __DIR__.'/vendor/autoload.php';

$app = require_once dirname(__FILE__).'/src/ChamiloLMS/app.php';

use Knp\Provider\ConsoleServiceProvider;

$app->register(
    new ConsoleServiceProvider(),
    array(
        'console.name'              => 'Chamilo CLI',
        'console.version'           => '1.0.0',
        'console.project_directory' => __DIR__.'/..'
    )
);


// Variable $helperSet is defined inside cli-config.php
//require __DIR__.'/console-config.php';
//$cli = new \Symfony\Component\Console\Application('Chamilo CLI');

// Adding commands.
/** @var Knp\Console\Application $cli */
$cli = $app['console'];

$cli->setCatchExceptions(true);

$helpers = array(
    'configuration' => new Chash\Helpers\ConfigurationHelper()
);
$helperSet = $cli->getHelperSet();
foreach ($helpers as $name => $helper) {
    $helperSet->set($helper, $name);
}

$cli->addCommands(
    array(
        // DBAL Commands.
        new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand(),
        new \Doctrine\DBAL\Tools\Console\Command\ImportCommand(),

        // ORM Commands.
        new \Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand(),
        new \Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand(),
        new \Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand(),
        new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand(),
        new \Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand(),
        new \Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand(),
        new \Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand(),
        new \Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand(),
        new \Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand(),
        new \Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand(),
        new \Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand(),
        new \Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand(),
        new \Doctrine\ORM\Tools\Console\Command\RunDqlCommand(),
        new \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand(),

        // Migrations Commands.
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand(),

        // Chamilo commands.

        new ChamiloLMS\Command\Template\AsseticDumpCommand(),
        new ChamiloLMS\Command\Translation\ExportLanguagesCommand(),

        // Chash commands.
        new Chash\Command\Database\RunSQLCommand(),
        //new Chash\Command\Database\DumpCommand(),
        //new Chash\Command\Database\RestoreCommand(),
        new Chash\Command\Database\SQLCountCommand(),
        new Chash\Command\Database\FullBackupCommand(),
        //new Chash\Command\Database\DropDatabaseCommand(),

        new Chash\Command\Files\CleanTempFolderCommand(),

        new Chash\Command\Translation\ExportLanguageCommand(),
        new Chash\Command\Translation\ImportLanguageCommand()
    )
);
$cli->run();
