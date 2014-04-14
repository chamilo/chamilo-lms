<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanDeletedDocmentsCommand
 * Clean the courses/[CODE]/documents/ directory, removing all documents and folders marked DELETED
 * @package Chash\Command\Files
 */
class CleanDeletedDocumentsCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:clean_deleted_documents')
            ->setDescription('Cleans the documents that were deleted but left as _DELETED_')
            ->addOption(
                'size',
                null,
                InputOption::VALUE_NONE,
                'Show the total size of space that will be freed. Requires more processing'
            )
            ->addOption(
                'list',
                null,
                InputOption::VALUE_NONE,
                'Show the complete list of files to be deleted before asking for confirmation'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $files = $this->getConfigurationHelper()->getDeletedDocuments();
        if ($input->isInteractive()) {
            $this->writeCommandHeader($output, 'Cleaning deleted documents.');
            $list = $input->getOption('list'); //1 if the option was set
            if ($list) {
                if (count($files) > 0) {
                    foreach ($files as $file) {
                        $output->writeln($file->getRealpath());
                    }
                } else {
                    $output->writeln('No file to be deleted in courses/ directory');
                    return;
                }
            }
            $stats = $input->getOption('size'); //1 if the option was set
            if ($stats) {
                $size = 0;
                foreach ($files as $file) {
                    $size += $file->getSize();
                }
                $output->writeln('Total size used by deleted documents: '.round(((float)$size/1024)/1024,2).'MB');
            }
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation(
                $output,
                '<question>Are you sure you want to clean the Chamilo deleted documents? (y/N)</question>',
                false
            )
            ) {
                return;
            }
        }
        $this->removeFiles($files, $output);
    }
}
