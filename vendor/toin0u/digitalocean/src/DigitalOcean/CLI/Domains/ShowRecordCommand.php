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
 * Command-line domains:records:show class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class ShowRecordCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('domains:records:show')
            ->addArgument('id', InputArgument::REQUIRED, 'The id or the name of the domain')
            ->addArgument('record_id', InputArgument::REQUIRED, 'The id of the record')
            ->setDescription('Show a specified domain record in your account')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $record       = $digitalOcean->domains()->getRecord($input->getArgument('id'), $input->getArgument('record_id'));

        $content   = array();
        $content[] = array(
            $record->status,
            $record->record->id,
            $record->record->domain_id,
            $record->record->record_type,
            $record->record->name,
            $record->record->data,
            $record->record->priority,
            $record->record->port,
            $record->record->weight,
        );
        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Status', 'ID', 'Domain ID', 'Type', 'Name', 'Data', 'Priority', 'Port', 'Weight'))
            ->setRows($content);

        $table->render($output);
    }
}
