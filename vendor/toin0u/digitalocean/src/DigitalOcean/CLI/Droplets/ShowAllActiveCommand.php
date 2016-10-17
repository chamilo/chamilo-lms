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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use DigitalOcean\CLI\Command;

/**
 * Command-line droplets:show-all-active class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class ShowAllActiveCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('droplets:show-all-active')
            ->setDescription('Return all active droplets that are currently running in your account')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $droplets     = $digitalOcean->droplets()->showAllActive()->droplets;

        $content = array();
        foreach ($droplets as $droplet) {
            $content[] = array(
                $droplet->id,
                $droplet->name,
                $droplet->image_id,
                $droplet->size_id,
                $droplet->region_id,
                $droplet->backups_active,
                $droplet->ip_address,
                $droplet->private_ip_address,
                $droplet->status,
                $droplet->locked,
                $droplet->created_at,
            );
        }

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array(
                'ID', 'Name', 'Image ID', 'Size ID', 'Region ID', 'Backups Active',
                'IP Address', 'Private IP Address', 'Status', 'Locked', 'Created At',
            ))
            ->setRows($content);

        $table->render($output);
    }
}
