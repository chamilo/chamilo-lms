<?php

namespace Chash\Command\Installation;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\ORM\Tools\Export\ExportException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console;

/**
 * Class WipeCommand
 * @package Chash\Command\Installation
 */
class WipeCommand extends CommonCommand
{
    protected function configure()
    {
        $this
            ->setName('chash:chamilo_wipe')
            ->setDescription('Prepares a portal for a new installation')
            ->addArgument('path', InputArgument::OPTIONAL, 'The path to the Chamilo folder');
    }

    /**
     * Executes a command via CLI
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Arguments
        $path = $input->getArgument('path');
        $dialog = $this->getHelperSet()->get('dialog');

        $configurationPath = $this->getConfigurationHelper()->getConfigurationPath($path);
        $configurationFilePath = $this->getConfigurationHelper()->getConfigurationFilePath($path);

        $this->writeCommandHeader($output, 'Wipe command.');

        $output->writeln("<comment>This command will clean your Chamilo installation. Removing the database and courses.</comment>");

        if ($configurationPath == false) {
            $output->writeln("<comment>A Chamilo installation was not detected. You can add a path: </comment><info>chamilo:wipe /var/www/chamilo </info>");
            return 0;
        } else {

            if (!$dialog->askConfirmation(
                $output,
                '<comment>A Chamilo configuration file was found here:</comment><info> '.$configurationPath.' </info> <question>Are you sure you want to continue?</question>(y/N)',
                false
            )
            ) {
                return 0;
            }
        }

        // $this->setConfigurationPath($configurationPath);

        $output->writeln("<comment>This command will clean your installation: drop db, removes config files, cache files.</comment>");

        // Drop database Chash command.

        $command = $this->getApplication()->find('db:drop_databases');

        $arguments = array(
            'command' => 'files:drop_databases',
            '--conf' => $configurationFilePath
        );

        $inputDrop = new ArrayInput($arguments);
        $command->run($inputDrop, $output);

        // Clean temp Chash command
        $command = $this->getApplication()->find('files:clean_temp_folder');

        $arguments = array(
            'command' => 'files:clean_temp_folder',
            '--conf' => $configurationFilePath
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);

        // Cleaning courses files
        $command = $this->getApplication()->find('files:clean_courses_files');

        $arguments = array(
            'command' => 'files:clean_courses_files',
            '--conf' => $configurationFilePath
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);

        // Cleaning config files (last one)

        $command = $this->getApplication()->find('files:clean_config_files');
        $arguments = array(
            'command' => 'files:clean_config_files',
            '--conf' => $configurationFilePath
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);
    }
}
