<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class GenerateTempFileStructureCommand
 * @package Chash\Command\Files
 */
class GenerateTempFileStructureCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:generate_temp_folders')
            ->setDescription('Generate temp folder structure: twig');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->writeCommandHeader($output, 'Generating temp folders.');

        // Data folders
        $files = $this->getConfigurationHelper()->getTempFolderList();
        $this->createFolders($output, $files, 0777);
    }

    /**
     * @param OutputInterface $output
     * @param array $files
     * @param $permission
     * @return int
     */
    public function createFolders(OutputInterface $output, $files, $permission)
    {
        $dryRun = $this->getConfigurationHelper()->getDryRun();

        if (empty($files)) {
            $output->writeln('<comment>No files found.</comment>');
            return 0;
        }

        $fs = new Filesystem();
        try {
            if ($dryRun) {
                $output->writeln("<comment>Folders to be created with permission ".decoct($permission).":</comment>");
                foreach ($files as $file) {
                    $output->writeln($file);
                }
            } else {
                $output->writeln("<comment>Creating folders with permission ".decoct($permission).":</comment>");
                foreach ($files as $file) {
                    $output->writeln($file);
                }
                $fs->mkdir($files, $permission);
            }

        } catch (IOException $e) {
            echo "\n An error occurred while removing the directory: ".$e->getMessage()."\n ";
        }
    }
}
