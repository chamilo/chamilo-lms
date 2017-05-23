<?php

namespace Chash\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ShowConnInfoCommand
 * @package Chash\Command\Database
 */
class ShowConnInfoCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('db:show_conn_info')
            ->setDescription('Shows database connection credentials for the current Chamilo install');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $dialog = $this->getHelperSet()->get('dialog');

        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to show the database connection info here? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        $_configuration = $this->getConfigurationArray();

        $output->writeln("Database connection details:");
        $output->writeln("Host:\t".$_configuration['db_host']);
        $output->writeln("User:\t".$_configuration['db_user']);
        $output->writeln("Pass:\t".$_configuration['db_password']);
        $output->writeln("DB:\t".$_configuration['main_database']);
        $output->writeln("Connection string (add password manually for increased security:");
        $output->writeln("mysql -h ".$_configuration['db_host']." -u ".$_configuration['db_user']." -p ".$_configuration['main_database']."\n");
    }
}
