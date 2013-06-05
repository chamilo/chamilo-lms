<?php

namespace ChamiloLMS\Component\DataFilesystem;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @todo use Gaufrette to manage course files (some day)
 * Class DataFilesystem
 * @package ChamiloLMS\Component\DataFilesystem
 */
class DataFilesystem
{
    private $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Gets a file from the /data folder
     * @param $file
     * @return SplFileInfo
     * @throws \InvalidArgumentException
     */
    public function get($file)
    {
        $file = new SplFileInfo($this->path.$file, null, null);
        $filesystem = new Filesystem();
        if ($filesystem->exists($file)) {
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


}
