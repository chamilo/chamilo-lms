<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\CoreBundle\Entity\Course;
use Exception;

/**
 * Class H5pPackageImporter.
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
     * Path to course directory
     * @var string
     */
    protected string $courseDirectoryPath;
    /**
     * @var array
     */
    protected array $packageFileInfo;
    /**
     * The package type is usually a MIME type
     * @var string
     */
    protected string $packageType;
    protected string $h5pJsonContent;
    /**
     * H5pPackageImporter constructor.
     */
    protected function __construct(array $fileInfo, Course $course)
    {
        $this->packageFileInfo = $fileInfo;
        $this->course = $course;
        $this->courseDirectoryPath = api_get_path(SYS_COURSE_PATH).$this->course->getDirectory();
        $this->packageType = $fileInfo['type'];

    }
    /**
     * @param array $fileInfo
     * @param Course $course
     * @return ZipPackageImporter
     * @throws Exception
     */
    public static function create(array $fileInfo, Course $course): ZipPackageImporter
    {
        if (
            'application/octet-stream' !== $fileInfo['type']
            && pathinfo($fileInfo['name'], PATHINFO_EXTENSION) !== 'h5p'
        ) {
            throw new Exception('Not a H5P package');
        }

        return new ZipPackageImporter($fileInfo, $course);

    }

    /**
     * Check the package and unzip it, checking if it has the 'h5p.json' file or some php script
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
