<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\DataFilesystem;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console;

/**
 * @todo use Gaufrette to manage course files (some day)
 * @todo add security restrictions.
 * Class DataFilesystem
 * @package ChamiloLMS\Component\DataFilesystem
 */
class DataFilesystem
{
    /** @var array chamilo paths */
    private $paths;

    /** @var \Symfony\Component\Filesystem\Filesystem  */
    private $fs;

    /**
     * @param array $paths
     * @param Filesystem $filesystem
     */
    public function __construct($paths, Filesystem $filesystem)
    {
        $this->paths = $paths;
        $this->fs = $filesystem;
    }

    /**
     * Gets a file from the "data" folder
     * @param string $file
     * @return SplFileInfo
     * @throws \InvalidArgumentException
     */
    public function get($file)
    {
        $file = new SplFileInfo($this->paths['sys_data_path'].$file, null, null);
        if ($this->fs->exists($file)) {
            return $file;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'The file "%s" does not exists .',
                    $file
                )
            );
        }
    }

    /**
     * Gets a file from the data/courses/MATHS/document directory
     * @param $courseCode
     * @param $file
     * @return SplFileInfo
     */
    public function getCourseDocument($courseCode, $file)
    {
        $file = 'courses/'.$courseCode.'/document/'.$file;
        return $this->get($file);
    }

     /**
     * Gets a file from the data/courses/MATHS/scorm directory
     * @param $courseCode
     * @param $file
     * @return SplFileInfo
     */
    public function getCourseScormDocument($courseCode, $file)
    {
        $file = 'courses/'.$courseCode.'/scorm/'.$file;
        return $this->get($file);
    }

    /**
     * Gets a file from the data/courses/MATHS/document directory
     * @param $courseCode
     * @param $file
     * @return SplFileInfo
     */
    public function getCourseUploadFile($courseCode, $file)
    {
        $file = 'courses/'.$courseCode.'/upload/'.$file;
        return $this->get($file);
    }

    /**
     * Create folders
     * @param array $folderList
     * @param Console\Output\OutputInterface  $output
     * @param string permissions
     */
    public function createFolders(array $folderList, Console\Output\OutputInterface $output = null, $folderPermissions = null)
    {
        if (empty($folderPermissions)) {
            $folderPermissions = api_get_permissions_for_new_directories();
        }

        if (!empty($folderList)) {
            foreach ($folderList as $folder) {
                if (!is_dir($folder)) {
                    $this->fs->mkdir($folder, $folderPermissions);
                    if ($output) {
                        $output->writeln("Folder <comment>'$folder'</comment> created");
                    }
                }
            }
        }
    }

    /**
     * @param array $folderList
     * @param Console\Output\OutputInterface  $output
     */
    public function copyFolders(array $folderList, Console\Output\OutputInterface $output = null)
    {
        if (!empty($folderList)) {
            foreach ($folderList as $folderSource => $folderDestination) {
                $this->fs->mirror($folderSource, $folderDestination);
                $finder = new Finder();
                $files = $finder->files()->in($folderDestination);
                $this->fs->chmod($files, api_get_permissions_for_new_directories());
                if ($output) {
                    $output->writeln("Contents were copied from <comment>$folderSource</comment> to <comment>$folderDestination</comment>");
                }
            }
        }
    }

    /**
     * @return Finder
     */
    public function getStyleSheetFolders()
    {
        $finder = new Finder();
        $styleSheetFolder = $this->paths['root_sys'].'main/css';
        return $finder->directories()->depth('== 0')->in($styleSheetFolder);
    }
}
