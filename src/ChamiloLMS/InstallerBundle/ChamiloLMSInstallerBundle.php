<?php

namespace ChamiloLMS\InstallerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Console\Application;
use ChamiloLMS\InstallerBundle\Command\InstallCommand;

/**
 * Class ChamiloLMSInstallerBundle
 * @package ChamiloLMS\InstallerBundle
 */
class ChamiloLMSInstallerBundle extends Bundle
{
    /**
     * @param Application $application
     */
    public function registerCommands(Application $application)
    {
        $application->addCommands(array(
            // DBAL Commands.
            new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand(),
            new \Doctrine\DBAL\Tools\Console\Command\ImportCommand(),

            // Migrations Commands.
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
            new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand(),

            // Chash commands.
            //new UpgradeCommand(),
            new InstallCommand(),
            new \Chash\Command\Installation\InstallCommand(),

            new \Chash\Command\Files\CleanCoursesFilesCommand(),
            new \Chash\Command\Files\CleanTempFolderCommand(),
            new \Chash\Command\Files\CleanConfigFilesCommand(),
            new \Chash\Command\Files\MailConfCommand(),
            new \Chash\Command\Files\SetPermissionsAfterInstallCommand(),
            new \Chash\Command\Files\GenerateTempFileStructureCommand()
        ));
    }
}
