<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use Chamilo\CoreBundle\Entity\Course;
use DocumentManager;
use Exception;
use PclZip;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class AbstractImporter.
 *
 * @package Chamilo\PluginBundle\XApi\Importer
 */
abstract class AbstractImporter
{
    /**
     * @var \Chamilo\CoreBundle\Entity\Course
     */
    protected $course;
    /**
     * @var string
     */
    protected $courseDirectoryPath;
    /**
     * @var string
     */
    protected $toolDirectory;
    /**
     * @var string
     */
    protected $packageDirectoryPath;
    /**
     * @var \PclZip
     */
    protected $zipFile;

    /**
     * AbstractImporter constructor.
     *
     * @param array                             $fileInfo
     * @param string                            $toolDirectory
     * @param \Chamilo\CoreBundle\Entity\Course $course
     */
    protected function __construct(array $fileInfo, string $toolDirectory, Course $course)
    {
        $this->course = $course;

        $pathInfo = pathinfo($fileInfo['name']);

        $this->courseDirectoryPath = api_get_path(SYS_COURSE_PATH).$this->course->getDirectory();
        $this->toolDirectory = $toolDirectory;
        $this->packageDirectoryPath = implode(
            '/',
            [
                $this->courseDirectoryPath,
                $this->toolDirectory,
                api_replace_dangerous_char($pathInfo['filename'])
            ]
        );

        $this->zipFile = new PclZip($fileInfo['tmp_name']);
    }

    /**
     * @param array                             $fileInfo
     * @param \Chamilo\CoreBundle\Entity\Course $course
     *
     * @return \Chamilo\PluginBundle\XApi\Importer\AbstractImporter
     */
    abstract public static function create(array $fileInfo, Course $course);

    /**
     * @throws \Exception
     */
    public function import()
    {
        $this->validPackage();

        if (!$this->isEnoughSpace()) {
            throw new Exception('Not enough space to storage package.');
        }

        $fs = new Filesystem();
        $fs->mkdir(
            $this->packageDirectoryPath,
            api_get_permissions_for_new_directories()
        );

        $this->zipFile->extract($this->packageDirectoryPath);

        return "{$this->packageDirectoryPath}/{$this->toolDirectory}.xml";
    }

    /**
     * @throws \Exception
     */
    protected function validPackage()
    {
        $zipContent = $this->zipFile->listContent();

        if (empty($zipContent)) {
            throw new Exception('Package file is empty');
        }

        foreach ($zipContent as $zipEntry) {
            if (preg_match('~.(php.*|phtml)$~i', $zipEntry['filename'])) {
                throw new Exception("File \"{$zipEntry['filename']}\" contains a PHP script");
            }
        }
    }

    /**
     * @return bool
     */
    private function isEnoughSpace()
    {
        $zipRealSize = array_reduce(
            $this->zipFile->listContent(),
            function ($accumulator, $zipEntry) {
                return $accumulator + $zipEntry['size'];
            }
        );

        $courseSpaceQuota = DocumentManager::get_course_quota($this->course->getCode());

        if (!enough_size($zipRealSize, $this->courseDirectoryPath, $courseSpaceQuota)) {
            return false;
        }

        return true;
    }
}
