<?php
/**
 * Command-line tool to do things more swiftly in Chamilo.
 * To add support for a new command see the Console Component read:
 *
 * https://speakerdeck.com/hhamon/symfony-extending-the-console-component
 * http://symfony.com/doc/2.0/components/console/introduction.html
 *
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @license This script is provided under the terms of the GNU/GPLv3+ license
 */

/* Security check: do not allow any other calling method than command-line */
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

$application->addCommands(
    array(
        // DBAL Commands.
        new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand(),
        //new \Doctrine\DBAL\Tools\Console\Command\ImportCommand(),

        // Migrations Commands.
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand(),

        // Chash commands

        new Chash\Command\Chash\SetupCommand(),
        new Chash\Command\Chash\SelfUpdateCommand(),

        new Chash\Command\Database\RunSQLCommand(),
        new Chash\Command\Database\ImportCommand(),
        new Chash\Command\Database\DumpCommand(),
        new Chash\Command\Database\RestoreCommand(),
        new Chash\Command\Database\SQLCountCommand(),
        new Chash\Command\Database\FullBackupCommand(),
        new Chash\Command\Database\DropDatabaseCommand(),
        new Chash\Command\Database\ShowConnInfoCommand(),

        new Chash\Command\Files\CleanConfigFilesCommand(),
        new Chash\Command\Files\CleanCoursesFilesCommand(),
        new Chash\Command\Files\CleanDeletedDocumentsCommand(),
        new Chash\Command\Files\CleanTempFolderCommand(),
        new Chash\Command\Files\ConvertVideosCommand(),
        new Chash\Command\Files\DeleteCoursesCommand(),
        new Chash\Command\Files\DeleteMultiUrlCommand(),
        new Chash\Command\Files\GenerateTempFileStructureCommand(),
        new Chash\Command\Files\MailConfCommand(),
        new Chash\Command\Files\SetPermissionsAfterInstallCommand(),
        new Chash\Command\Files\ShowDiskUsageCommand(),
        new Chash\Command\Files\UpdateDirectoryMaxSizeCommand(),
        new Chash\Command\Files\ReplaceURLCommand(),

        new Chash\Command\Info\WhichCommand(),
        new Chash\Command\Info\GetInstancesCommand(),

        new Chash\Command\Installation\InstallCommand(),
        new Chash\Command\Installation\WipeCommand(),
        new Chash\Command\Installation\StatusCommand(),
        new Chash\Command\Installation\UpgradeCommand(),

        new Chash\Command\Translation\AddSubLanguageCommand(),
        new Chash\Command\Translation\DisableLanguageCommand(),
        new Chash\Command\Translation\EnableLanguageCommand(),
        new Chash\Command\Translation\ExportLanguageCommand(),
        new Chash\Command\Translation\ImportLanguageCommand(),
        new Chash\Command\Translation\ListLanguagesCommand(),
        new Chash\Command\Translation\PlatformLanguageCommand(),
        new Chash\Command\Translation\TermsPackageCommand(),

        new Chash\Command\User\ChangePassCommand(),
        new Chash\Command\User\DisableAdminsCommand(),
        new Chash\Command\User\MakeAdminCommand(),
        new Chash\Command\User\ResetLoginCommand(),
        new Chash\Command\User\SetLanguageCommand(),
        new Chash\Command\User\UsersPerUrlAccessCommand(),
    )
);
$application->run();
