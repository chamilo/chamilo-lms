<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PackageImporter.
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
        $this->courseDirectoryPath = $this->resolveStoragePath();
    }

    /**
     * Resolve persistent storage for plugin packages in Chamilo 2.
     */
    protected function resolveStoragePath(): string
    {
        $basePath = Container::$container->getParameter('chamilo.plugin.storage_dir');

        $path = rtrim((string) $basePath, '/').'/XApi/course_'.$this->course->getId();

        $filesystem = new Filesystem();
        $filesystem->mkdir($path, api_get_permissions_for_new_directories());

        return $path;
    }

    /**
     * @return XmlPackageImporter|ZipPackageImporter
     */
    public static function create(array $fileInfo, Course $course)
    {
        if ('text/xml' === $fileInfo['type']) {
            return new XmlPackageImporter($fileInfo, $course);
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
