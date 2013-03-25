<?php

namespace Chash\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Count the number of rows in a specific table
 * @return mixed Integer number of rows, or null on error
 */
class SQLCountCommand extends CommonChamiloDatabaseCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('db:sql_count')
            ->setDescription('Count the number of rows in a specific table')
            ->addArgument(
                'table',
                InputArgument::REQUIRED,
                'Name of the table'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $table = $input->getArgument('table');
        $_configuration = $this->getHelper('configuration')->getConfiguration();
        $connection = $this->getHelper('configuration')->getConnection();

        $t = mysql_real_escape_string($table);
        $q = mysql_query('SELECT COUNT(*) FROM '.$t);
        if ($q !== false) {
            $r = mysql_fetch_row($q);
            $n = $r[0];
            $output->writeln(
                '<comment>Database/table/number of rows: </comment><info>'.$_configuration['main_database'].'/'.$t.'/'.$n.'</info>'
            );
        } else {
            $output->writeln(
                "<comment>Table '$table' does not exists in the database: ".$_configuration['main_database']
            );
        }
    }
}