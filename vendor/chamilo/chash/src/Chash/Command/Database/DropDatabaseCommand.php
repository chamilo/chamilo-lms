<?php

namespace Chash\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DropDatabaseCommand
 * @package Chash\Command\Database
 */
class DropDatabaseCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('db:drop_databases')
            ->setDescription('Drops all databases from the current Chamilo install');
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
            '<question>Are you sure you want to drop all database in this portal? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you really sure? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        $_configuration = $this->getConfigurationArray();
        $connection = $this->getConnection($input);

        if ($connection) {

            $list = $_configuration = $this->getHelper('configuration')->getAllDatabases();
            $currentDatabases = $connection->getSchemaManager()->listDatabases();
            if (is_array($list)) {
                $output->writeln('<comment>Starting Chamilo drop database process.</comment>');
                foreach ($list as $db) {
                    if (in_array($db, $currentDatabases)) {
                        $output->writeln("Dropping DB: $db");
                        $connection->getSchemaManager()->dropDatabase($db);
                    } else {
                        $output->writeln("DB: $db was already dropped.");
                    }
                }
                $output->writeln('<comment>End drop database process.</comment>');
            }
        } else {
            $output->writeln("<comment>Can't established connection with the database. Probably it was already deleted.</comment>");
        }
    }
}
