<?php

// For licensing terms, see /license.txt

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\CoreBundle\Entity\Course;

/**
 * Class H5pPackageImporter.
 */
abstract class H5pPackageImporter
{
    /**
     * @var Course
     */
    protected $course;

    /**
     * Path to course directory.
     *
     * @var string
     */
    protected $courseDirectoryPath;

    /**
     * @var array
     */
    protected $packageFileInfo;

    /**
     * The package type is usually a MIME type.
     *
     * @var string
     */
    protected $packageType;
    protected $h5pJsonContent;

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
     * @throws \Exception
     */
    public static function create(array $fileInfo, Course $course): ZipPackageImporter
    {
        if (
            'application/octet-stream' !== $fileInfo['type']
            && 'h5p' !== pathinfo($fileInfo['name'], PATHINFO_EXTENSION)
        ) {
            throw new \Exception('Not a H5P package');
        }

        return new ZipPackageImporter($fileInfo, $course);
    }

    /**
     * Check the package and unzip it, checking if it has the 'h5p.json' file or some php script.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    abstract public function import(): string;

    public function getPackageType(): string
    {
        return $this->packageType;
    }
}
