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
 * Command-line droplets:rebuild class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class RebuildCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('droplets:rebuild')
            ->setDescription('Reinstall a droplet with a default image')
            ->addArgument('id', InputArgument::REQUIRED, 'The droplet id')
            ->addArgument('image_id', InputArgument::REQUIRED, 'The image id')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getHelperSet()->get('dialog')->askConfirmation(
            $output,
            sprintf('<question>Are you sure to rebuild this droplet %s with this image id %s ? (y/N)</question> ',
                $input->getArgument('id'), $input->getArgument('image_id')),
            false
        )) {
            $output->writeln('Aborted!');

            return;
        }

        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $droplet      = $digitalOcean->droplets()->rebuild(
            $input->getArgument('id'), array('image_id' => (int) $input->getArgument('image_id'))
        );

        $content   = array();
        $content[] = array($droplet->status, $droplet->event_id,);
        $table     = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Status', 'Event ID'))
            ->setRows($content);

        $table->render($output);
    }
}
