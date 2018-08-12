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
 * Command-line domains:records:destroy class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class DestroyRecordCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('domains:records:destroy')
            ->addArgument('id', InputArgument::REQUIRED, 'The id or the name of the domain')
            ->addArgument('record_id', InputArgument::REQUIRED, 'The id of the record')
            ->setDescription('Delete the specified domain record from your account - this is irreversible !')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getHelperSet()->get('dialog')->askConfirmation(
            $output,
            sprintf(
                '<question>Are you sure to destroy this domain %s record %s ? (y/N)</question> ',
                $input->getArgument('id'), $input->getArgument('record_id')
            ),
            false
        )) {
            $output->writeln('Aborted!');

            return;
        }

        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $record       = $digitalOcean->domains()->destroyRecord($input->getArgument('id'), $input->getArgument('record_id'));


        $content   = array();
        $content[] = array($record->status);
        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Status'))
            ->setRows($content);

        $table->render($output);
    }
}
