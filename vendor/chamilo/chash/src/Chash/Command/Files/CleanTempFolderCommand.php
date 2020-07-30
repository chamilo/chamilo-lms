<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanTempFolderCommand
 * @package Chash\Command\Files
 */
class CleanTempFolderCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:clean_temp_folder')
            ->setAliases(array('fct'))
            ->setDescription('Cleans the temp directory.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->writeCommandHeader($output, "Cleaning temp files.");

        $dialog = $this->getHelperSet()->get('dialog');

        if (PHP_SAPI == 'cli') {
            if ($input->isInteractive()) {
                if (!$dialog->askConfirmation(
                    $output,
                    '<question>Are you sure you want to clean the Chamilo temp files? (y/N)</question>',
                    false
                )
                ) {
                    return;
                }
            }
        }

        $files = $this->getConfigurationHelper()->getTempFiles();
        $this->removeFiles($files, $output);
    }
}
