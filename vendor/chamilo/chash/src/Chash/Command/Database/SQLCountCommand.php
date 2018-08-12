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
class SQLCountCommand extends CommonDatabaseCommand
{
    /**
     *
     */
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

    /**
     * @todo use doctrine
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $table = $input->getArgument('table');
        $_configuration = $this->getConfigurationArray();
        $connection = $this->getConnection($input);
        $tableExists = $connection->getSchemaManager()->tablesExist($table);
        if ($tableExists) {
            $sql = "SELECT COUNT(*) count FROM $table";
            $stmt = $connection->query($sql);
            $result = $stmt->fetch();
            $count = $result['count'];
            $output->writeln(
                '<comment>Database/table/number of rows: </comment><info>'.$_configuration['main_database'].'/'.$table.'/'.$count.'</info>'
            );
        } else {
            $output->writeln(
                "<comment>Table '$table' does not exists in the database: ".$_configuration['main_database']
            );
        }
    }
}
