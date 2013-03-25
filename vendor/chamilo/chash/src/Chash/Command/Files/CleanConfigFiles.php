<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonChamiloDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clean the archives directory, leaving only index.html, twig and Serializer
 * @return bool True on success, false on error
 */

class CleanConfigFiles extends CommonChamiloDatabaseCommand
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:clean_config_files')
            ->setDescription('Cleans the archives directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $output->writeln('<comment>Starting cleaning your Chamilo installation</comment>');

        $dialog = $this->getHelperSet()->get('dialog');

        if (!$dialog->askConfirmation(
            $output,
            '<question>Are you sure you want to clean your config files? (y/N)</question>',
            false
        )
        ) {
            return;
        }

        $filesToDelete = $this->getHelper('configuration')->getConfigFiles();

        if (!empty($filesToDelete)) {
            foreach ($filesToDelete as $file) {
                if (isset($file) && file_exists($file) && is_file($file)) {
                    if (!$dialog->askConfirmation(
                        $output,
                        "<question>Are you sure you want to delete: </question> <info>$file</info> (y/N)",
                        false
                    )
                    ) {
                        return;
                    }
                    unlink($file);
                    $output->writeln("<comment>File deleted: </comment><info>$file</info>");
                }
            }
        } else {
            $output->writeln("<comment>Nothing to delete</comment>");
        }
    }
}