<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
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
        $this->courseDirectoryPath = $this->resolveRuntimeStoragePath();
    }

    /**
     * Keep the runtime copy in the legacy local storage so TinCan/cmi5 launch
     * behavior remains unchanged.
     */
    protected function resolveRuntimeStoragePath(): string
    {
        $basePath = Container::$container->getParameter('chamilo.plugin.storage_dir');

        $path = rtrim((string) $basePath, '/').'/XApi/course_'.$this->course->getId();

        $filesystem = new Filesystem();
        $filesystem->mkdir($path, api_get_permissions_for_new_directories());

        return $path;
    }

    protected function getRuntimeStoragePath(): string
    {
        return $this->courseDirectoryPath;
    }

    protected function getPersistentStoragePrefix(): string
    {
        return 'XApi/course_'.$this->course->getId();
    }

    /**
     * Mirror a locally extracted runtime package into the persistent plugins filesystem.
     *
     * @throws Exception
     */
    protected function syncRuntimeDirectoryToPersistentStorage(string $runtimeDirectoryPath): void
    {
        $runtimeDirectoryPath = rtrim(str_replace('\\', '/', $runtimeDirectoryPath), '/');
        $runtimeBasePath = rtrim(str_replace('\\', '/', $this->getRuntimeStoragePath()), '/');

        if ('' === $runtimeDirectoryPath || !is_dir($runtimeDirectoryPath)) {
            throw new Exception('The runtime package directory does not exist.');
        }

        if (0 !== strpos($runtimeDirectoryPath, $runtimeBasePath.'/') && $runtimeDirectoryPath !== $runtimeBasePath) {
            throw new Exception('The runtime package directory is outside the XApi runtime storage.');
        }

        $relativeDirectory = ltrim(substr($runtimeDirectoryPath, strlen($runtimeBasePath)), '/');
        $persistentDirectory = $this->joinPersistentPath($relativeDirectory);

        $pluginsFilesystem = Container::getPluginsFileSystem();

        try {
            $pluginsFilesystem->deleteDirectory($persistentDirectory);
        } catch (\Throwable $throwable) {
            // Ignore cleanup failures for directories that do not exist yet.
        }

        $pluginsFilesystem->createDirectory($persistentDirectory);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($runtimeDirectoryPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $localPath = str_replace('\\', '/', $item->getPathname());
            $relativePath = ltrim(substr($localPath, strlen($runtimeDirectoryPath)), '/');
            $storagePath = $persistentDirectory.( '' !== $relativePath ? '/'.$relativePath : '' );

            if ($item->isDir()) {
                $pluginsFilesystem->createDirectory($storagePath);
                continue;
            }

            $stream = @fopen($item->getPathname(), 'rb');

            if (false === $stream) {
                throw new Exception(sprintf('Unable to read runtime file "%s".', $item->getPathname()));
            }

            try {
                $pluginsFilesystem->writeStream($storagePath, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
    }

    protected function joinPersistentPath(string $relativePath = ''): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        $basePath = $this->getPersistentStoragePrefix();

        return '' === $relativePath ? $basePath : $basePath.'/'.$relativePath;
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
