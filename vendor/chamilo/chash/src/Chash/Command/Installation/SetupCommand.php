<?php

namespace Chash\Command\Installation;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;

/**
 * Class MigrationCommand
 */
class SetupCommand extends CommonCommand
{
    protected function configure()
    {
        $this
            ->setName('chamilo:setup')
            ->setDescription('Prepares a portal for a new installation')
            ->addArgument('path', InputArgument::OPTIONAL, 'The path to the chamilo folder');
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
        // Arguments
        $path = $input->getArgument('path');
        $dialog = $this->getHelperSet()->get('dialog');
        $configurationPath = $this->getConfigurationHelper()->getNewConfigurationPath($path);

        $output->writeln("<comment>This command will clean your Chamilo installation.</comment>");

        if ($configurationPath == false) {
            $output->writeln("<comment>A Chamilo installation was not detected. You can add a path to the command: </comment><info>chamilo:setup /var/www/chamilo </info>");
            return 0;
        } else {

            if (!$dialog->askConfirmation(
                $output,
                '<comment>A Chamilo installation was found here:</comment><info> '.$configurationPath.' </info> <question>Are you sure you want to continue?</question>(y/N)',
                false
            )
            ) {
                return 0;
            }
        }

        $output->writeln("<comment>This command will clean your installation: drop db, removes config files, cache files</comment>");

        //Drop database chash command
        $command = $this->getApplication()->find('db:drop_databases');

        $arguments = array(
            'command' => 'db:drop_databases'
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);

        //Clean temp chash command
        $command = $this->getApplication()->find('files:clean_archives');

        $arguments = array(
            'command' => 'files:clean_archives'
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);

        //Clean files
        $command = $this->getApplication()->find('files:clean_config_files');

        $arguments = array(
            'command' => 'files:clean_archives'
        );
        $input = new ArrayInput($arguments);
        $command->run($input, $output);

        $output->writeln("<comment>Cleaned.</comment>");
    }
}

