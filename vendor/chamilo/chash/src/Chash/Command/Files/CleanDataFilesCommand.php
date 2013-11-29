<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonChamiloDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanDataFilesCommand
 * Clean the archives directory, leaving only index.html, twig and Serializer
 * @package Chash\Command\Files
 */
class CleanDataFilesCommand extends CommonChamiloDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:clean_data_files')
            ->setDescription('Cleans the data directory');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $this->writeCommandHeader($output, 'Cleaning data folders.');

        $dialog = $this->getHelperSet()->get('dialog');

        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to clean the Chamilo data files? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        $files = $this->getConfigurationHelper()->getDataFiles();
        $this->removeFiles($files, $output);
    }
}
