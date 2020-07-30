<?php

namespace Chash\Command\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Class CommonChamiloUserCommand
 * @package Chash\Command\User
 */
class CommonChamiloUserCommand extends Command
{
    /**
     *
     */
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $input->getOption('conf');
        $this->getHelper('configuration')->readConfigurationFile($configuration);
    }
}
