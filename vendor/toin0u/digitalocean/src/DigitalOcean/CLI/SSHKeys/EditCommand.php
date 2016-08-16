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
 * Command-line ssh-keys:edit class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class EditCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ssh-keys:edit')
            ->addArgument('id', InputArgument::REQUIRED, 'The SSH key id to edit')
            ->addArgument('ssh_pub_key', InputArgument::REQUIRED, 'The new public SSH key')
            ->setDescription('Edit an existing public SSH key in your account')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getHelperSet()->get('dialog')->askConfirmation(
            $output,
            sprintf('<question>Are you sure to edit this SSH key %s ? (y/N)</question> ', $input->getArgument('id')),
            false
        )) {
            $output->writeln('Aborted!');

            return;
        }

        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $sshKey       = $digitalOcean->sshKeys()->edit(
            $input->getArgument('id'),
            array('ssh_pub_key' => $input->getArgument('ssh_pub_key'))
        );

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
