<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanConfigFilesCommand
 * Clean the archives directory, leaving only index.html, twig and Serializer
 * @package Chash\Command\Files
 */
class CleanConfigFilesCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:clean_config_files')
            ->setDescription('Cleans the config files to help you re-install');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->writeCommandHeader($output, "Cleaning config files.");

        $dialog = $this->getHelperSet()->get('dialog');

        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to clean your config files? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        $files = $this->getConfigurationHelper()->getConfigFiles();
        $this->removeFiles($files, $output);

    }
}
