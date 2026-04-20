<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\CoreBundle\Entity\Course;
use Exception;

/**
 * Base importer for H5P packages.
 */
abstract class H5pPackageImporter
{
    protected Course $course;
    protected string $courseStoragePath;
    protected array $packageFileInfo;
    protected string $packageType = 'h5p';

    protected function __construct(array $fileInfo, Course $course)
    {
        $this->packageFileInfo = $fileInfo;
        $this->course = $course;
        $this->courseStoragePath = H5pPackageTools::getCourseTemporaryStoragePath($course);
    }

    /**
     * @throws Exception
     */
    public static function create(array $fileInfo, Course $course): ZipPackageImporter
    {
        $extension = strtolower((string) pathinfo($fileInfo['name'] ?? '', PATHINFO_EXTENSION));

        if ('h5p' !== $extension) {
            throw new Exception('Not a H5P package.');
        }

        return new ZipPackageImporter($fileInfo, $course);
    }

    /**
     * @throws Exception
     */
    abstract public function import(): string;

    public function getPackageType(): string
    {
        return $this->packageType;
    }
}
