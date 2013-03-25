<?php

namespace ChamiloLMS\Command\Database;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;

/**
 * Class MigrationCommand
 */
class SetupCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('chamilo:setup')
            ->setDescription('Prepares a portal for a new installation');
            //->addOption('configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a migrations configuration file.');
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
        $output->writeln("<comment>This command will clean your installation: drop db, removes config files, cache files</comment>");

        //Drop database chash command
        $command = $this->getApplication()->find('db:drop_databases');

        $arguments = array(
            'command' => 'db:drop_databases'
        );
        $input     = new ArrayInput($arguments);
        $command->run($input, $output);

        //Clean temp chash command
        $command = $this->getApplication()->find('files:clean_archives');

        $arguments = array(
            'command' => 'files:clean_archives'
        );
        $input     = new ArrayInput($arguments);
        $command->run($input, $output);

        //Clean files
        $command = $this->getApplication()->find('files:clean_config_files');

        $arguments = array(
            'command' => 'files:clean_archives'
        );
        $input     = new ArrayInput($arguments);
        $command->run($input, $output);

        $output->writeln("<comment>Cleaned</comment>");
    }
}

