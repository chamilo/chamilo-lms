<?php

namespace Chash\Command\Chash;

use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Composer\Util\RemoteFilesystem;
use Symfony\Component\Yaml\Dumper;
use Composer\IO\NullIO;
use Alchemy\Zippy\Zippy;

/**
 * Class SelfUpdateCommand
 * @package Chash\Command\Chash
 */
class SelfUpdateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('chash:self-update')
            ->setAliases(array('selfupdate'))
            ->addOption('temp-folder', null, InputOption::VALUE_OPTIONAL, 'The temp folder', '/tmp')
            ->addOption('src-destination', null, InputOption::VALUE_OPTIONAL, 'The destination folder')
            ->setDescription('Updates chash to the latest version');
    }

    /**
     * Executes a command via CLI
     *
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $tempFolder = $input->getOption('temp-folder');
        $destinationFolder = $input->getOption('src-destination');

        if (empty($destinationFolder)) {
            $destinationFolder =  realpath(__DIR__.'/../../../../');
        }

        if (!is_writable($destinationFolder)) {
            $output->writeln('Chash update failed: the "'.$destinationFolder.'" directory used to update the Chash file could not be written');
            return 0;
        }

        if (!is_writable($tempFolder)) {
            $output->writeln('Chash update failed: the "'.$tempFolder.'" directory used to download the temp file could not be written');
            return 0;
        }

        //$protocol = extension_loaded('openssl') ? 'https' : 'http';
        $protocol = 'http';
        $rfs = new RemoteFilesystem(new NullIO());

        // Chamilo version
        //$latest = trim($rfs->getContents('version.chamilo.org', $protocol . '://version.chamilo.org/version.php', false));
        //https://github.com/chamilo/chash/archive/master.zip
        $tempFile = $tempFolder.'/chash-master.zip';
        $rfs->copy('github.com', 'https://github.com/chamilo/chash/archive/master.zip', $tempFile);

        if (!file_exists($tempFile)) {
            $output->writeln('Chash update failed: the "'.$tempFile. '" file could not be written');
            return 0;
        }

        $folderPath = $tempFolder.'/chash';
        if (!is_dir($folderPath)) {
            mkdir($folderPath);
        }

        $zippy = Zippy::load();
        $archive = $zippy->open($tempFile);
        try {
            $archive->extract($folderPath);
        } catch (\Alchemy\Zippy\Exception\RunTimeException $e) {
            $output->writeln("<comment>Chash update failed during unzip.");
            $output->writeln($e->getMessage());
            return 0;
        }
        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $fs->mirror($folderPath.'/chash-master', $destinationFolder, null, array('override' => true));
        $output->writeln('Copying '.$folderPath.'/chash-master to '.$destinationFolder);
    }
}
