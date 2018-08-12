<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\CLI\SSHKeys;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use DigitalOcean\CLI\Command;

/**
 * Command-line ssh-keys:add class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class AddCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ssh-keys:add')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the new SSH key')
            ->addArgument('ssh_pub_key', InputArgument::REQUIRED, 'The SSH key string')
            ->setDescription('Add a new public SSH key to your account')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $sshKey       = $digitalOcean->sshKeys()->add(array(
            'name'        => $input->getArgument('name'),
            'ssh_pub_key' => $input->getArgument('ssh_pub_key'),
        ));

        $content   = array();
        $content[] = array(
            $sshKey->status,
            $sshKey->ssh_key->id,
            $sshKey->ssh_key->name,
            wordwrap($sshKey->ssh_key->ssh_pub_key, 50, "\n", true)
        );

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Status', 'ID', 'Name', 'Pub Key'))
            ->setRows($content);

        $table->render($output);
    }
}
