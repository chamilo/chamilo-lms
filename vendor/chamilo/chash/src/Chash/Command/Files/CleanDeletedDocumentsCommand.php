<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanDeletedDocumentsCommand
 * Clean the courses/[CODE]/documents/ directory, removing all documents
 * and folders marked DELETED
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
            )
            ->addOption(
                'from-db',
                null,
                InputOption::VALUE_NONE,
                'Also delete items from the c_document table'
            )
            ->addOption(
                'category',
                null,
                InputOption::VALUE_OPTIONAL,
                'Only delete items from courses in this category (given as category code)'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $category = $input->getOption('category');
        $courseDirsList = array();
        if (!empty($category)) {
            $courseDirsList = '';
            $connection = $this->getConnection($input);
            // @todo escape the $category properly
            $sql = "SELECT directory FROM course WHERE category_code = '$category'";
            $stmt = $connection->query($sql);
            while ($row = $stmt->fetch()) {
                $courseDirsList[] = $row['directory'];
            }
        }
        $files = $this->getConfigurationHelper()->getDeletedDocuments($courseDirsList);
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
            $deleteFromDb = $input->getOption('from-db');
            if ($deleteFromDb) {
                $connection = $this->getConnection($input);
                $sql = "DELETE FROM c_document WHERE path LIKE '%_DELETED%'";
                $stmt = $connection->query($sql);
                /*
                while ($row = $stmt->fetch()) {
                    $sql2 = "SELECT id FROM c_item_property
                        WHERE c_id = " . $row['c_id'] . "
                            AND tool = 'document'
                            AND ref = ".$row['id'];
                    $stmt2 = $connection->query($sql2);
                    while ($row2 = $stmt2->fetch()) {
                        $output->writeln($row['c_id'] . ' ' . $row2['id']);
                    }

                }
                */
                $output->writeln(
                    'Deleted all database references in c_document.
                     Table c_item_property left untouched, to keep history.'
                );
            }
        }
        $this->removeFiles($files, $output);
    }
}
