<?php

namespace Chash\Command\Files;

use Chash\Command\Database\CommonDatabaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ConvertVideosCommand
 * Convert all videos found in the given directory (recursively)
 * to the given format, using ffmpeg
 * @package Chash\Command\Files
 */
class ConvertVideosCommand extends CommonDatabaseCommand
{
    public $excluded = array();
    public $ext;
    public $origExt;
    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('files:convert_videos')
            ->setDescription('Converts all videos found in the given directory (recursively) to the given format, using the ffmpeg command line')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'The directory containing the videos, as an absolute path'
            )
            ->addOption(
                'ext',
                null,
                InputOption::VALUE_REQUIRED,
                'The extension of the files to be found and converted - defaults to "webm"'
            )
            ->addOption(
                'orig-ext',
                null,
                InputOption::VALUE_REQUIRED,
                'The extension that we want to add to the original files. Defaults to "orig", so video.webm will be saved as video.orig.webm. Use "none" to skip saving the original.'
            )
            ->addOption(
                'fps',
                null,
                InputOption::VALUE_REQUIRED,
                'The fps we want the final videos to be outputted in. Defaults to 24'
            )
            ->addOption(
                'bitrate',
                null,
                InputOption::VALUE_REQUIRED,
                'The bitrate (~image quality) we want to export in, expressed in Kbits. Defaults to 512Kbits.'
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

        if ($input->isInteractive()) {
            $this->writeCommandHeader($output, 'Looking for videos...');
            $confPath = $this->getConfigurationHelper()->getConfigurationFilePath();
            $sysPath = $this->getConfigurationHelper()->getSysPathFromConfigurationFile($confPath);

            $dir = $input->getArgument('source'); //1 if the option was set
            if (substr($dir,0,1) != '/') {
                $dir = $sysPath . $dir;
            }
            if (!is_dir($dir)) {
                $output->writeln($dir. ' was not confirmed as a directory (if not starting with /, it is considered as relative to Chamilo\'s root folder)');
                return;
            }
            $this->ext = $input->getOption('ext');
            if (empty($this->ext)) {
                $this->ext = 'webm';
            }
            $this->origExt = $input->getOption('orig-ext');
            if (empty($this->origExt)) {
                $this->origExt = 'orig';
            }
            $fps = $input->getOption('fps');
            if (empty($fps)) {
                $fps = '24';
            }
            $bitRate = $input->getOption('bitrate');
            if (empty($bitRate)) {
                $bitRate = '512';
            }
            $vcodec = 'copy';
            if ($this->ext == 'webm') {
                $vcodec = 'libvpx';
            }

            // Find the files we want to treat, using Finder selectors
            $finder = new Finder();
            $filter = function (\SplFileInfo $file, $ext, $orig) {
                $combinedExt = '.'.$orig.'.'.$ext;
                $combinedExtLength = strlen($combinedExt);
                $extLength = strlen('.' . $ext);
                if (substr($file->getRealPath(),-$combinedExtLength) == $combinedExt) {
                    return false;
                }
                if (is_file(substr($file->getRealPath(),0,-$extLength) . $combinedExt)) {
                    $this->excluded[] = $file;
                    return false;
                }
            };

            $finder->sortByName()->files()->in($dir)
                ->name('*.'.$this->ext)
                ->filter($filter, $this->ext, $this->origExt);

            // Print the list of matching files we found
            if (count($finder) > 0) {
                $output->writeln('Videos found for conversion: ');
                foreach ($finder as $file) {
                    $output->writeln($file->getRealpath());
                }
            } else {
                if (count($this->excluded) > 0) {
                    $output->writeln('The system has detected several videos already converted: ');
                    foreach ($this->excluded as $file) {
                        $output->writeln('- '.$file->getRealPath());
                    }
                }
                $output->writeln('No video left to convert');
                return;
            }

            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation(
                $output,
                '<question>All listed videos will be altered and a copy of the original will be taken with a .orig.webm extension. Are you sure you want to proceed? (y/N)</question>',
                false
            )
            ) {
                return;
            }
            $fs = new Filesystem();
            $time = time();
            $counter = 0;
            $sizeNew = $sizeOrig = 0;
            foreach ($finder as $file) {
                $sizeOrig += $file->getSize();
                $origName = $file->getRealPath();
                $newName = substr($file->getRealPath(),0,-4).'orig.webm';
                $fs->rename($origName, $newName);
                $out = array();
                $newNameCommand = preg_replace('/\s/','\ ',$newName);
                $newNameCommand = preg_replace('/\(/','\(',$newNameCommand);
                $newNameCommand = preg_replace('/\)/','\)',$newNameCommand);
                $origNameCommand = preg_replace('/\s/','\ ',$origName);
                $origNameCommand = preg_replace('/\(/','\(',$origNameCommand);
                $origNameCommand = preg_replace('/\)/','\)',$origNameCommand);
                $output->writeln('ffmpeg -i ' . $newNameCommand . ' -b ' . $bitRate . 'k -f ' . $this->ext . ' -vcodec ' . $vcodec . ' -acodec copy -r ' . $fps . ' ' . $origNameCommand);
                $exec = @system('ffmpeg -i ' . $newNameCommand . ' -b ' . $bitRate . 'k -f ' . $this->ext . ' -vcodec ' . $vcodec . ' -acodec copy -r ' . $fps . ' ' . $origNameCommand, $out);
                $sizeNew += filesize($origName);
                $counter ++;
            }
        }
        $output->writeln('');
        $output->writeln('Done converting all videos from '.$dir);
        $output->writeln('Total videos converted: ' . $counter . ' videos in ' . (time() - $time) .' seconds');
        $output->writeln('Total size of old videos combined: ' . round($sizeOrig/(1024*1024)).'M');
        $output->writeln('Total size of all new videos combined: ' . round($sizeNew/(1024*1024)).'M');
        //$this->removeFiles($files, $output);
    }
}
