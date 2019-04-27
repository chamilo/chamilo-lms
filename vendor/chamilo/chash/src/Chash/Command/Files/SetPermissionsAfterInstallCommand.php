<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class SetPermissionsAfterInstallCommand
 * @package Chash\Command\Files
 */
class SetPermissionsAfterInstallCommand extends CommonDatabaseCommand
{
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:set_permissions_after_install')
            ->setDescription('Set permissions')
            ->addOption('linux-user', null, InputOption::VALUE_OPTIONAL, 'user', 'www-data')
            ->addOption('linux-group', null, InputOption::VALUE_OPTIONAL, 'group', 'www-data');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->writeCommandHeader($output, 'Setting permissions ...');

        $linuxUser = $input->getOption('linux-user');
        $linuxGroup = $input->getOption('linux-group');

        // All files
        $files = $this->getConfigurationHelper()->getSysFolders();
        $this->setPermissions($output, $files, 0777, $linuxUser, $linuxGroup, false);

        $files = $this->getConfigurationHelper()->getSysFiles();
        $this->setPermissions($output, $files, null, $linuxUser, $linuxGroup, false);

        // Data folders
        $files = $this->getConfigurationHelper()->getDataFolders();
        $this->setPermissions($output, $files, 0777, $linuxUser, $linuxGroup);

        // Config folders
        $files = $this->getConfigurationHelper()->getConfigFolders();
        $this->setPermissions($output, $files, 0555, $linuxUser, $linuxGroup);
        $files = $this->getConfigurationHelper()->getConfigFiles();
        $this->setPermissions($output, $files, 0555, $linuxUser, $linuxGroup);

        // Temp folders
        $files = $this->getConfigurationHelper()->getTempFolders();
        $this->setPermissions($output, $files, 0777, $linuxUser, $linuxGroup);
    }

    /**
     * @param OutputInterface $output
     * @param array $files
     * @param $permission
     * @param $user
     * @param $group
     * @param bool $listFiles
     * @return int
     */
    public function setPermissions(
        OutputInterface $output,
        $files,
        $permission,
        $user,
        $group,
        $listFiles = true
    ) {
        $dryRun = $this->getConfigurationHelper()->getDryRun();

        if (empty($files)) {
            $output->writeln('<comment>No files found.</comment>');
            return 0;
        }

        $fs = new Filesystem();
        try {
            if ($dryRun) {
                $output->writeln("<comment>Modifying files permission to: ".decoct($permission)."</comment>");
                $output->writeln("<comment>user: ".$user."</comment>");
                $output->writeln("<comment>group: ".$group."</comment>");
                if ($listFiles) {
                    $output->writeln("<comment>Files: </comment>");
                    foreach ($files as $file) {
                        $output->writeln($file->getPathName());
                    }
                }
            } else {

                if (!empty($permission)) {
                    $output->writeln("<comment>Modifying files permission to: ".decoct($permission)."</comment>");
                }
                if (!empty($user)) {
                    $output->writeln("<comment>Modifying file user: ".$user."</comment>");
                }
                if (!empty($group)) {
                    $output->writeln("<comment>Modifying file group: ".$group."</comment>");
                }

                if ($listFiles) {
                    $output->writeln("<comment>Files: </comment>");
                    foreach ($files as $file) {
                        $output->writeln($file->getPathName());
                    }
                } else {
                    $output->writeln("<comment>Skipping file list (too long)... </comment>");
                }

                if (!empty($permission)) {
                    $fs->chmod($files, $permission, 0000, true);
                }

                if (!empty($user)) {
                    //$fs->chown($files, $user, true);
                }

                if (!empty($group)) {
                    //$fs->chgrp($files, $group, true);
                }
            }
        } catch (IOException $e) {
            echo "\n An error occurred while removing the directory: ".$e->getMessage()."\n ";
        }
    }
}
