<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\CoreBundle\Entity\Course;
use Exception;

/**
 * Class AbstractImporter.
 *
 * @package Chamilo\PluginBundle\H5pImport\H5pImporter
 */
abstract class H5pPackageImporter
{
    /**
     * @var Course
     */
    protected Course $course;
    /**
     * @var string
     */
    protected string $courseDirectoryPath;
    /**
     * @var array
     */
    protected array $packageFileInfo;
    /**
     * @var string
     */
    protected string $packageType;

    protected string $h5pJsonContent;

    /**
     * AbstractImporter constructor.
     */
    protected function __construct(array $fileInfo, Course $course)
    {
        $this->packageFileInfo = $fileInfo;
        $this->course = $course;
        $this->courseDirectoryPath = api_get_path(SYS_COURSE_PATH).$this->course->getDirectory();
        $this->packageType = $fileInfo['type'];

    }

    /**
     * @return ZipPackageImporter
     */
    public static function create(array $fileInfo, Course $course)
    {
        if ('text/xml' === $fileInfo['type']) {
            // cambiar a error comun
            return new XmlPackageImporter($fileInfo, $course);
        }

        return new ZipPackageImporter($fileInfo, $course);
    }

    /**
     * @throws Exception
     *
     * @return mixed
     */
    abstract public function import(): string;

    public function getPackageType(): string
    {
        return $this->packageType;
    }
}
