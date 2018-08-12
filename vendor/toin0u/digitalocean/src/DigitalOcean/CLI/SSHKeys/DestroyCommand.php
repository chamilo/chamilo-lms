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
 * Command-line ssh-keys:destroy class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class DestroyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ssh-keys:destroy')
            ->addArgument('id', InputArgument::REQUIRED, 'The SSH key id')
            ->setDescription('Delete the SSH key from your account - this is irreversible !')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getHelperSet()->get('dialog')->askConfirmation(
            $output,
            sprintf('<question>Are you sure to destroy this SSH key %s ? (y/N)</question> ', $input->getArgument('id')),
            false
        )) {
            $output->writeln('Aborted!');

            return;
        }

        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $sshKey       = $digitalOcean->sshKeys()->destroy($input->getArgument('id'));

        $content   = array();
        $content[] = array(
            $sshKey->status,
        );

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Status'))
            ->setRows($content);

        $table->render($output);
    }
}
