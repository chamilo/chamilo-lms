<?php

namespace Chash\Command\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class CommonChamiloDatabaseCommand extends Command
{
    protected function configure()
    {
        $this
            ->addOption(
                'conf',
                null,
                InputOption::VALUE_NONE,
                'Set a configuration file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $input->getOption('conf');
        $this->getHelper('configuration')->readConfigurationFile($configuration);
    }
}