<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use Chamilo\CoreBundle\Entity\Course;

/**
 * Class AbstractImporter.
 *
 * @package Chamilo\PluginBundle\XApi\Importer
 */
abstract class PackageImporter
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
     * @var array
     */
    protected $packageFileInfo;
    /**
     * @var string
     */
    protected $packageType;

    /**
     * AbstractImporter constructor.
     */
    protected function __construct(array $fileInfo, Course $course)
    {
        $this->packageFileInfo = $fileInfo;
        $this->course = $course;

        $this->courseDirectoryPath = api_get_path(SYS_COURSE_PATH).$this->course->getDirectory();
    }

    /**
     * @return \Chamilo\PluginBundle\XApi\Importer\XmlPackageImporter|\Chamilo\PluginBundle\XApi\Importer\ZipPackageImporter
     */
    public static function create(array $fileInfo, Course $course)
    {
        if ('text/xml' === $fileInfo['type']) {
            return new XmlPackageImporter($fileInfo, $course);
        }

        return new ZipPackageImporter($fileInfo, $course);
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    abstract public function import(): string;

    public function getPackageType(): string
    {
        return $this->packageType;
    }
}
