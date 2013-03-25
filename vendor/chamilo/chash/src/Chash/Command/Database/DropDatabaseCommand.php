<?php

namespace Chash\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DropDatabaseCommand extends CommonChamiloDatabaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('db:drop_databases')
            ->setDescription('Drops all databases from the current Chamilo install');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $dialog = $this->getHelperSet()->get('dialog');

        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to drop all database in this portal? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        if (!$dialog->askConfirmation(
            $output,
            '<question>Really sure? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        $_configuration = $this->getHelper('configuration')->getConfiguration();
        $connection = $this->getHelper('configuration')->getConnection();

        if ($connection) {
            $cmd  = 'mysql -h '.$_configuration['db_host'].' -u '.$_configuration['db_user'].' -p'.$_configuration['db_password'].' -e "DROP DATABASE %s"';
            $list = $_configuration = $this->getHelper('configuration')->getAllDatabases();
            if (is_array($list)) {
                $output->writeln('<comment>Starting Chamilo process</comment>');
                foreach ($list as $db) {
                    $c = sprintf($cmd, $db);
                    $output->writeln("Dropping DB: $db");
                    $err = @system($c);
                }
                $output->writeln('<comment>End Chamilo process</comment>');
            }
        } else {
            $output->writeln("<comment>Can't stablished connection with the database</comment>");
        }
    }
}