<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\CLI\Droplets;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use DigitalOcean\CLI\Command;

/**
 * Command-line droplets:snapshot class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class SnapshotCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('droplets:snapshot')
            ->setDescription('Take a snapshot of the running droplet')
            ->addArgument('id', InputArgument::REQUIRED, 'The droplet id')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the snapshot')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));

        if ($input->getArgument('name')) {
            $droplet = $digitalOcean->droplets()->snapshot(
                $input->getArgument('id'), array('name' => $input->getArgument('name'))
            );
        } else {
            $droplet = $digitalOcean->droplets()->snapshot($input->getArgument('id'));
        }

        $content   = array();
        $content[] = array($droplet->status, $droplet->event_id,);
        $table     = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Status', 'Event ID'))
            ->setRows($content);

        $table->render($output);
    }
}
