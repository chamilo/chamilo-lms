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
    protected $workspacePath;

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
        $this->workspacePath = Container::getCacheDir().'plugins/XApi/'.uniqid('import_', true);
    }

    /**
     * Local directory where the package is extracted before it is copied to the plugins filesystem.
     */
    protected function getWorkspacePath(): string
    {
        return $this->workspacePath;
    }

    protected function removeWorkspace(): void
    {
        (new Filesystem())->remove($this->workspacePath);
    }

    protected function getPersistentStoragePrefix(): string
    {
        return 'XApi/course_'.$this->course->getId();
    }

    protected function buildPersistentPackagePrefix(string $packageType, string $packageName): string
    {
        return $this->joinPersistentPath($packageType.'/'.api_replace_dangerous_char($packageName));
    }

    protected function buildStorageUri(string $storagePath): string
    {
        return 'storage://'.ltrim($storagePath, '/');
    }

    /**
     * Copy a locally extracted package into the plugins filesystem.
     *
     * @throws Exception
     */
    protected function syncWorkspaceDirectoryToPersistentStorage(string $runtimeDirectoryPath): void
    {
        $runtimeDirectoryPath = rtrim(str_replace('\\', '/', $runtimeDirectoryPath), '/');
        $runtimeBasePath = rtrim(str_replace('\\', '/', $this->getWorkspacePath()), '/');

        if ('' === $runtimeDirectoryPath || !is_dir($runtimeDirectoryPath)) {
            throw new Exception('The extracted package directory does not exist.');
        }

        if (0 !== strpos($runtimeDirectoryPath, $runtimeBasePath.'/') && $runtimeDirectoryPath !== $runtimeBasePath) {
            throw new Exception('The extracted package directory is outside the XApi workspace.');
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
