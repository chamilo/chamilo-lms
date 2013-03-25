<?php

namespace Chash\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Command functions meant to deal with what the user of this script is calling
 * it for.
 */
/**
 * Returns a dump of the database (caller should use an output redirect of some kind to store
 * to a file)
 */
class DumpCommand extends CommonChamiloDatabaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('db:dump')
            ->setDescription('Outputs a dump of the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $_configuration = $this->getHelper('configuration')->getConfiguration();
        $dump           = 'mysqldump -h '.$_configuration['db_host'].' -u '.$_configuration['db_user'].' -p'.$_configuration['db_password'].' '.$_configuration['main_database'];
        system($dump);
        return null;
    }
}