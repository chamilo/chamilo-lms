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
 * Command-line ssh-keys:show class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class ShowCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ssh-keys:show')
            ->addArgument('id', InputArgument::REQUIRED, 'The SSH key id')
            ->setDescription('Show a specific public SSH key in your account')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $sshKey       = $digitalOcean->sshKeys()->show($input->getArgument('id'))->ssh_key;

        $content   = array();
        $content[] = array(
            $sshKey->id,
            $sshKey->name,
            wordwrap($sshKey->ssh_pub_key, 50, "\n", true)
        );

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('ID', 'Name', 'Pub Key'))
            ->setRows($content);

        $table->render($output);
    }
}
