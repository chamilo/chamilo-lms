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
 * Command-line domains:add class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class AddCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('domains:add')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the new domain')
            ->addArgument('ip_address', InputArgument::REQUIRED, 'The IP address')
            ->setDescription('Add a new domain with an A record for the specified IP address')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $domain       = $digitalOcean->domains()->add(array(
            'name'       => $input->getArgument('name'),
            'ip_address' => $input->getArgument('ip_address'),
        ));

        $content   = array();
        $content[] = array($domain->status, $domain->domain->id, $domain->domain->name);
        $table     = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Status', 'ID', 'Name'))
            ->setRows($content);

        $table->render($output);
    }
}
