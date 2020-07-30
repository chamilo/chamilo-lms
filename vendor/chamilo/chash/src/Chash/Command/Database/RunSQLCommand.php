<?php

namespace Chash\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Connects to the MySQL client without the need to introduce a password
 * @return int Exit code returned by mysql command
 */
class RunSQLCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('db:sql_cli')
            ->setAliases(array('dbc', 'dbcli'))
            ->setDescription('Enters to the SQL command line');
        $this->setHelp('Prompts a SQL cli');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $output->writeln('Starting Chamilo SQL cli');

        $_configuration = $this->getConfigurationArray();

        $cmd         = 'mysql -h '.$_configuration['db_host'].' -u '.$_configuration['db_user'].' -p'.$_configuration['db_password'].' '.$_configuration['main_database'];
        $process     = proc_open($cmd, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes);
        $proc_status = proc_get_status($process);
        $exit_code   = proc_close($process);
        return ($proc_status["running"] ? $exit_code : $proc_status["exitcode"]);

        /*$output->writeln('<comment>Starting Chamilo process</comment>');
        $output->writeln('<info>Chamilo process ended succesfully</info>');
        */
        /*
        $progress = $this->getHelperSet()->get('progress');

        $progress->start($output, 50);
        $i = 0;
        while ($i++ < 50) {
            // ... do some work

            // advance the progress bar 1 unit
            $progress->advance();
        }
        $progress->finish();*/

        // Inside execute function
        //$output->getFormatter()->setStyle('fcbarcelona', new OutputFormatterStyle('red', 'blue', array('blink', 'bold', 'underscore')));
        //$output->writeln('<fcbarcelona>Messi for the win</fcbarcelona>');
    }
}
