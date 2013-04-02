<?php

namespace ChamiloLMS\Command\Database;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Symfony\Component\Yaml\Dumper;

/**
 * Class InstallExtendCommand
 */
class InstallExtendCommand extends InstallCommand
{
    protected function configure()
    {
        $this
            ->setName('chamilo:install_extend')
            ->setDescription('Execute a Chamilo installation to a specified version')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to migrate to.', null);
    }

    /**
     * Executes a command via CLI
     *
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $path = $input->getOption('path');
        $configurationPath = $this->getHelper('configuration')->getConfigurationPath($path);

        $dialog = $this->getHelperSet()->get('dialog');

        $version = $input->getArgument('version');

        $defaultVersion = $this->getLatestVersion();

        if (empty($version)) {
            $version = $defaultVersion;
        }

        $output->writeln("<comment>Welcome to the Chamilo $version installation process.</comment>");

        if (!is_writable($configurationPath)) {
            $output->writeln("<comment>Folder ".$configurationPath." must be writable</comment>");
            exit;
        }

        $sqlFolder = $this->getInstallationPath($version);

        if (!is_dir($sqlFolder)) {
            $output->writeln("<comment>Sorry you can't install that version of Chamilo :( Supported versions:</comment> <info>".implode(', ', $this->getAvailableVersions()));
            exit;
        }

        if (file_exists($configurationPath.'configuration.php') || file_exists($configurationPath.'configuration.yml')) {
            $output->writeln("<comment>There's a Chamilo portal here ".$configurationPath." you must run</comment> <info>chamilo:setup </info><comment>if you want a fresh install.</comment>");
            exit;
            /*
            if (!$dialog->askConfirmation(
                $output,
                '<question>There is a Chamilo installation located here:</question> '.$configurationPath.' <question>Are you sure you want to continue?</question>(y/N)',
                false
            )
            ) {
                return;
            }

            if (!$dialog->askConfirmation(
                $output,
                '<comment>This will be a fresh installation. Old databases and config files will be deleted. </comment></info> <question>Are you sure?</question>(y/N)',
                false
            )
            ) {
                return;
            }
            $this->cleanInstallation($output);*/
        }

        //Getting default configuration parameters
        require_once api_get_path(SYS_PATH).'main/install/configuration.dist.yml.php';

        $avoidVariables = array(
            'main_database', //default is chamilo
            'db_glue',
            'table_prefix',
            'code_append',
            'course_folder',
            'db_admin_path',
            'cdn_enable',
            'verbose_backup',
            'session_stored_in_db',
            'session_lifetime',
            'software_name',
            'software_url',
            'deny_delete_users',
            'system_version',
            'system_stable'
        );

        $newConfigurationArray = array();
        foreach ($_configuration as $key => $value) {
            if (in_array($key, $avoidVariables)) {
                $newConfigurationArray[$key] = $value;
                continue;
            }
            if (!is_array($value)) {
                $data = $dialog->ask(
                    $output,
                    "Please enter the value of the $key ($value): ",
                    $value
                );
                $newConfigurationArray[$key] = $data;
            } else {
                $newConfigurationArray[$key] = $value;
            }
        }

        $configurationWasSaved = $this->writeConfiguration($newConfigurationArray, $version);

        if ($configurationWasSaved) {

            //Installing database
            $result = $this->install($version, $newConfigurationArray, $output);

            if ($result) {
                $this->createAdminUser($newConfigurationArray, $output);

                $output->writeln("<comment>Chamilo was successfully installed. Go to your browser and enter:</comment> <info>".$newConfigurationArray['root_web']);
            }
        }
    }

}