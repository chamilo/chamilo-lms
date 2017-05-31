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
 * Command-line droplets:create class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class CreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('droplets:create')
            ->setDescription('Create a new droplet')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the new droplet')
            ->addArgument('size_id', InputArgument::REQUIRED, 'The size id')
            ->addArgument('image_id', InputArgument::REQUIRED, 'The image id')
            ->addArgument('region_id', InputArgument::REQUIRED, 'The region id')
            ->addArgument('ssh_key_ids', InputArgument::OPTIONAL, 'The comma separated ssh keys ids')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $droplet      = $digitalOcean->droplets()->create(array(
            'name'        => $input->getArgument('name'),
            'size_id'     => (int) $input->getArgument('size_id'),
            'image_id'    => (int) $input->getArgument('image_id'),
            'region_id'   => (int) $input->getArgument('region_id'),
            'ssh_key_ids' => $input->getArgument('ssh_key_ids'),
        ));

        $content   = array();
        $content[] = array($droplet->status, $droplet->droplet->event_id,);
        $table     = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Status', 'Event ID'))
            ->setRows($content);

        $table->render($output);
    }
}
