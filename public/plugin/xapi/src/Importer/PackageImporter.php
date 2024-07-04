<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use Chamilo\CoreBundle\Entity\Course;
use Exception;

/**
 * Class AbstractImporter.
 */
abstract class PackageImporter
{
    /**
     * @var Course
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
     * @return mixed
     *
     * @throws Exception
     */
    abstract public function import(): string;

    public function getPackageType(): string
    {
        return $this->packageType;
    }
}
