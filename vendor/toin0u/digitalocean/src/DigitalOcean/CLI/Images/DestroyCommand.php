<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\CLI\Images;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use DigitalOcean\CLI\Command;

/**
 * Command-line images:destroy class.
 *
 * @author Antoine Corcy <contact@sbin.dk>
 */
class DestroyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('images:destroy')
            ->setDescription('Destroy an image - this is irreversible !')
            ->addArgument('id', InputArgument::REQUIRED, 'The image id')
            ->addOption('credentials', null, InputOption::VALUE_REQUIRED,
                'If set, the yaml file which contains your credentials', Command::DEFAULT_CREDENTIALS_FILE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getHelperSet()->get('dialog')->askConfirmation(
            $output,
            sprintf('<question>Are you sure to destroy this image %s ? (y/N)</question> ', $input->getArgument('id')),
            false
        )) {
            $output->writeln('Aborted!');

            return;
        }

        $digitalOcean = $this->getDigitalOcean($input->getOption('credentials'));
        $image        = $digitalOcean->images()->destroy($input->getArgument('id'));

        $content   = array();
        $content[] = array($image->status);
        $table     = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Status'))
            ->setRows($content);

        $table->render($output);
    }
}
