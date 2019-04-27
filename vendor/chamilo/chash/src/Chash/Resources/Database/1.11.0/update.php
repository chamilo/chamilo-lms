<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @param $_configuration
 * @param $mainConnection
 * @param $courseList
 * @param $dryRun
 * @param OutputInterface $output
 * @param $upgrade
 */
$updateFiles = function($_configuration, $mainConnection, $courseList, $dryRun, $output, $upgrade)
{
    $sysPath = $upgrade->getRootSys();
    $sysCodePath = $upgrade->getRootSys().'main/';

    $output->writeln('update.php');
    try {
        $fs = new Filesystem();

        $exercisePath = $sysCodePath . 'exercice';
        if (is_dir($exercisePath)) {
            $output->writeln("Remove $exercisePath");
            $fs->remove($exercisePath);
        }
        // Same with main/newscorm, renamed main/lp
        $lpPath = $sysCodePath . 'newscorm';
        if (is_dir($lpPath)) {
            $output->writeln("Remove $lpPath");
            $fs->remove($lpPath);
        }

        $ticketPluginPath = $sysPath . 'plugin/ticket';
        if (is_dir($ticketPluginPath)) {
            $output->writeln("Remove $ticketPluginPath");
            $fs->remove($ticketPluginPath);
        }

        $output->writeln('Folders cleaned up');
    } catch (Exception $e) {
        echo $e->getMessage();
    }
};
