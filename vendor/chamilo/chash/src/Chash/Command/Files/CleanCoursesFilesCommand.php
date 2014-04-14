<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanCoursesFilesCommand
 * Clean the courses directory, leaving only index.html, twig and Serializer
 * @package Chash\Command\Files
 */
class CleanCoursesFilesCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:clean_courses_files')
            ->setAliases(array('ccf'))
            ->setDescription('Cleans the courses directory');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->writeCommandHeader($output, 'Cleaning folders in courses directory.');
        $dialog = $this->getHelperSet()->get('dialog');

        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to clean this Chamilo install\'s courses files? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        $files = $this->getConfigurationHelper()->getCoursesFiles();
        $this->removeFiles($files, $output);
    }
}
