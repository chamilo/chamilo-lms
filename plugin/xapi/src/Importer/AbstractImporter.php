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
    protected $relCourseDir;
    /**
     * @var string
     */
    protected $packageDir;
    /**
     * @var \PclZip
     */
    protected $zipFile;

    /**
     * AbstractImporter constructor.
     *
     * @param array                             $fileInfo
     * @param string                            $directoryName
     * @param \Chamilo\CoreBundle\Entity\Course $course
     */
    protected function __construct(array $fileInfo, $directoryName, Course $course)
    {
        $this->course = $course;
        $this->relCourseDir = api_get_course_path($this->course->getCode()).'/'.$directoryName;
        $this->zipFile = new PclZip($fileInfo['tmp_name']);

        $filePathInfo = pathinfo($fileInfo['name']);
        $fileBaseName = str_replace(".{$filePathInfo['extension']}", '', $filePathInfo['basename']);

        $this->packageDir = '/'.api_replace_dangerous_char($fileBaseName);
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

        $sysCourseDir = api_get_path(SYS_COURSE_PATH).$this->relCourseDir;

        if (!$this->isEnoughSpace()) {
            throw new Exception('Not enough space to storage package.');
        }

        $fullSysPackageDir = $sysCourseDir.$this->packageDir;

        $fs = new Filesystem();
        $fs->mkdir(
            $fullSysPackageDir,
            api_get_permissions_for_new_directories()
        );

        $this->zipFile->extract($fullSysPackageDir);

        return "$fullSysPackageDir/tincan.xml";
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

        $sysCourseDir = api_get_path(SYS_COURSE_PATH).$this->relCourseDir;

        if (!enough_size($zipRealSize, $sysCourseDir, $courseSpaceQuota)) {
            return false;
        }

        return true;
    }
}
