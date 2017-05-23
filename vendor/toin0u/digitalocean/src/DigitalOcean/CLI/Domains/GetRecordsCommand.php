<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\CLI\Domains;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use DigitalOcean\CLI\Command;

/**
 * Command-line domains:records:all class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class GetRecordsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('domains:records:all')
            ->addArgument('id', InputArgument::REQUIRED, 'The id or the name of the domain')
            ->setDescription('Return all of your current domain records')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $records      = $digitalOcean->domains()->getRecords($input->getArgument('id'))->records;

        $content = array();
        foreach ($records as $i => $record) {
            $content[] = array(
                $record->id,
                $record->domain_id,
                $record->record_type,
                $record->name,
                $record->data,
                $record->priority,
                $record->port,
                $record->weight
            );
        }

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('ID', 'Domain ID', 'Type', 'Name', 'Data', 'Priority', 'Port', 'Weight'))
            ->setRows($content);

        $table->render($output);
    }
}
