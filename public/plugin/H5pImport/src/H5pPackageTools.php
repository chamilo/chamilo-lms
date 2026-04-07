<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportLibrary;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Filesystem\Filesystem;

class H5pPackageTools
{
    /**
     * Read and decode a JSON file.
     *
     * @return mixed
     */
    public static function getJson(string $file, bool $assoc = false)
    {
        $fs = new Filesystem();

        if (!$fs->exists($file)) {
            return false;
        }

        $contents = file_get_contents($file);

        if (false === $contents) {
            return false;
        }

        $json = json_decode($contents, $assoc);

        return null === $json ? false : $json;
    }

    public static function getStorageBasePath(): string
    {
        $basePath = Container::$container->getParameter('chamilo.plugin.storage_dir');
        $path = rtrim((string) $basePath, '/').'/H5pImport';

        $filesystem = new Filesystem();
        $filesystem->mkdir($path, api_get_permissions_for_new_directories());

        return $path;
    }

    public static function getTemporaryStorageBasePath(): string
    {
        $path = rtrim(self::getStorageBasePath(), '/').'/tmp';

        $filesystem = new Filesystem();
        $filesystem->mkdir($path, api_get_permissions_for_new_directories());

        return $path;
    }

    public static function getPluginsFilesystem(): FilesystemOperator
    {
        return Container::$container->get('oneup_flysystem.plugins_filesystem');
    }

    public static function getPersistentStorageRootPrefix(): string
    {
        return 'H5pImport';
    }

    public static function getCoursePersistentStoragePrefix(Course $course): string
    {
        return self::getPersistentStorageRootPrefix().'/'.self::buildCourseRelativePrefix($course);
    }

    public static function getCoursePersistentContentPrefix(Course $course): string
    {
        return self::getCoursePersistentStoragePrefix($course).'/content';
    }

    public static function getCoursePersistentLibrariesPrefix(Course $course): string
    {
        return self::getCoursePersistentStoragePrefix($course).'/libraries';
    }

    public static function ensureCoursePersistentStorage(Course $course): void
    {
        $filesystem = self::getPluginsFilesystem();
        $filesystem->createDirectory(self::getCoursePersistentStoragePrefix($course));
        $filesystem->createDirectory(self::getCoursePersistentContentPrefix($course));
        $filesystem->createDirectory(self::getCoursePersistentLibrariesPrefix($course));
    }

    public static function normalizePluginStorageRelativePath(string $path): ?string
    {
        $normalizedPath = self::normalizeRelativeStoragePath($path);

        if (null === $normalizedPath) {
            return null;
        }

        $rootPrefix = self::getPersistentStorageRootPrefix();

        if (!str_starts_with($normalizedPath, $rootPrefix.'/')) {
            $normalizedPath = $rootPrefix.'/'.$normalizedPath;
        }

        return self::normalizeRelativeStoragePath($normalizedPath);
    }

    public static function persistentFileExists(string $path): bool
    {
        $normalizedPath = self::normalizePluginStorageRelativePath($path);

        if (null === $normalizedPath) {
            return false;
        }

        return self::getPluginsFilesystem()->fileExists($normalizedPath);
    }

    public static function readPersistentFile(string $path)
    {
        $normalizedPath = self::normalizePluginStorageRelativePath($path);

        if (null === $normalizedPath) {
            return false;
        }

        return self::getPluginsFilesystem()->read($normalizedPath);
    }

    public static function readPersistentStream(string $path)
    {
        $normalizedPath = self::normalizePluginStorageRelativePath($path);

        if (null === $normalizedPath) {
            return false;
        }

        return self::getPluginsFilesystem()->readStream($normalizedPath);
    }

    public static function getPersistentFileSize(string $path): ?int
    {
        $normalizedPath = self::normalizePluginStorageRelativePath($path);

        if (null === $normalizedPath) {
            return null;
        }

        return self::getPluginsFilesystem()->fileSize($normalizedPath);
    }

    public static function getPersistentMimeType(string $path): ?string
    {
        $normalizedPath = self::normalizePluginStorageRelativePath($path);

        if (null === $normalizedPath) {
            return null;
        }

        return self::getPluginsFilesystem()->mimeType($normalizedPath);
    }

    public static function writeLocalFileToPersistentStorage(string $sourcePath, string $targetPath): void
    {
        $normalizedTargetPath = self::normalizePluginStorageRelativePath($targetPath);

        if (null === $normalizedTargetPath) {
            throw new \RuntimeException('Unable to resolve the target persistent storage path.');
        }

        $stream = fopen($sourcePath, 'rb');

        if (false === $stream) {
            throw new \RuntimeException(sprintf('Unable to open the source file "%s".', $sourcePath));
        }

        try {
            self::getPluginsFilesystem()->writeStream($normalizedTargetPath, $stream);
        } finally {
            fclose($stream);
        }
    }

    public static function writeLocalDirectoryToPersistentStorage(string $sourceDirectory, string $targetPrefix): void
    {
        $normalizedTargetPrefix = self::normalizePluginStorageRelativePath($targetPrefix);

        if (null === $normalizedTargetPrefix) {
            throw new \RuntimeException('Unable to resolve the target persistent storage directory.');
        }

        $sourceDirectory = rtrim($sourceDirectory, '/');

        if (!is_dir($sourceDirectory)) {
            throw new \RuntimeException(sprintf('The source directory "%s" does not exist.', $sourceDirectory));
        }

        self::getPluginsFilesystem()->createDirectory($normalizedTargetPrefix);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $absoluteItemPath = str_replace('\\', '/', $item->getPathname());
            $relativeItemPath = ltrim(substr($absoluteItemPath, strlen(str_replace('\\', '/', $sourceDirectory))), '/');
            $targetPath = $normalizedTargetPrefix.'/'.self::normalizeRelativeStoragePath($relativeItemPath);

            if ($item->isDir()) {
                self::getPluginsFilesystem()->createDirectory($targetPath);
                continue;
            }

            $stream = fopen($item->getPathname(), 'rb');

            if (false === $stream) {
                throw new \RuntimeException(sprintf('Unable to open the source file "%s".', $item->getPathname()));
            }

            try {
                self::getPluginsFilesystem()->writeStream($targetPath, $stream);
            } finally {
                fclose($stream);
            }
        }
    }

    public static function deletePersistentDirectory(string $path): void
    {
        $normalizedPath = self::normalizePluginStorageRelativePath($path);

        if (null === $normalizedPath) {
            return;
        }

        self::getPluginsFilesystem()->deleteDirectory($normalizedPath);
    }


    public static function readTextFromStoredPath(string $path)
    {
        $path = trim($path);

        if ('' === $path) {
            return false;
        }

        if (self::isAbsolutePath($path)) {
            if (!is_file($path) || !is_readable($path)) {
                return false;
            }

            return file_get_contents($path);
        }

        $normalizedPluginPath = self::normalizePluginStorageRelativePath($path);

        if (null !== $normalizedPluginPath) {
            try {
                if (self::persistentFileExists($normalizedPluginPath)) {
                    return self::readPersistentFile($normalizedPluginPath);
                }
            } catch (\Throwable $e) {
                error_log('[H5pImport][storage][read] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
            }
        }

        $legacyPath = rtrim(self::getStorageBasePath(), '/').'/'.ltrim($path, '/');

        if (!is_file($legacyPath) || !is_readable($legacyPath)) {
            return false;
        }

        return file_get_contents($legacyPath);
    }

    /**
     * @return mixed
     */
    public static function getJsonFromStoredPath(string $path, bool $assoc = false)
    {
        $contents = self::readTextFromStoredPath($path);

        if (false === $contents) {
            return false;
        }

        $json = json_decode((string) $contents, $assoc);

        return null === $json ? false : $json;
    }

    public static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || (bool) preg_match('~^[A-Za-z]:[\\/]~', $path);
    }

    public static function resolveUniquePersistentPackageRelativePath(Course $course, string $baseName): string
    {
        $safeBaseName = trim(api_replace_dangerous_char($baseName));

        if ('' === $safeBaseName) {
            $safeBaseName = 'package_'.uniqid();
        }

        $coursePrefix = self::buildCourseRelativePrefix($course).'/content';
        $candidate = self::normalizeRelativeStoragePath($coursePrefix.'/'.$safeBaseName);

        if (null === $candidate) {
            throw new \RuntimeException('Unable to resolve the persistent package path.');
        }

        $counter = 1;

        while (self::persistentFileExists($candidate.'/h5p.json')) {
            $candidate = self::normalizeRelativeStoragePath($coursePrefix.'/'.$safeBaseName.'_'.$counter);
            ++$counter;

            if (null === $candidate) {
                throw new \RuntimeException('Unable to resolve a unique persistent package path.');
            }
        }

        return $candidate;
    }

    public static function buildCourseRelativePrefix(Course $course): string
    {
        return 'course_'.$course->getId();
    }

    public static function getCourseTemporaryStoragePath(Course $course): string
    {
        $path = rtrim(self::getTemporaryStorageBasePath(), '/').'/'.self::buildCourseRelativePrefix($course);

        $filesystem = new Filesystem();
        $filesystem->mkdir($path, api_get_permissions_for_new_directories());
        $filesystem->mkdir($path.'/content', api_get_permissions_for_new_directories());
        $filesystem->mkdir($path.'/libraries', api_get_permissions_for_new_directories());
        $filesystem->mkdir($path.'/uploads', api_get_permissions_for_new_directories());

        return $path;
    }

    public static function getCourseStoragePath(Course $course): string
    {
        return self::getCourseTemporaryStoragePath($course);
    }


    public static function cleanupTemporaryCourseStorage(Course $course): void
    {
        $workspacePath = self::getCourseTemporaryStoragePath($course);
        $filesystem = new Filesystem();

        foreach (['uploads', 'content', 'libraries'] as $subDirectory) {
            $path = $workspacePath.'/'.$subDirectory;

            if (!$filesystem->exists($path)) {
                continue;
            }

            if (self::isDirectoryEmpty($path)) {
                $filesystem->remove($path);
            }
        }

        if ($filesystem->exists($workspacePath) && self::isDirectoryEmpty($workspacePath)) {
            $filesystem->remove($workspacePath);
        }
    }

    private static function isDirectoryEmpty(string $path): bool
    {
        if (!is_dir($path)) {
            return true;
        }

        $iterator = new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);

        return !$iterator->valid();
    }

    public static function normalizeRelativeStoragePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ('' === $path) {
            return null;
        }

        $segments = explode('/', $path);
        $normalized = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if ('' === $segment || '.' === $segment) {
                continue;
            }

            if ('..' === $segment) {
                if (empty($normalized)) {
                    return null;
                }

                array_pop($normalized);
                continue;
            }

            $normalized[] = $segment;
        }

        if (empty($normalized)) {
            return null;
        }

        return implode('/', $normalized);
    }

    public static function buildPackageAssetUrl(string $relativePath): string
    {
        $relativePath = self::normalizeRelativeStoragePath($relativePath);

        if (null === $relativePath) {
            return '';
        }

        $params = ['path' => $relativePath];
        $cid = api_get_course_int_id();
        $sid = api_get_session_id();
        $gid = api_get_group_id();

        if ($cid > 0) {
            $params['cid'] = $cid;
        }

        if ($sid >= 0) {
            $params['sid'] = $sid;
        }

        if ($gid >= 0) {
            $params['gid'] = $gid;
        }

        return api_get_path(\WEB_PLUGIN_PATH).'H5pImport/package_asset.php?'.http_build_query($params);
    }

    public static function getCourseLibrariesAssetBasePath(Course $course): string
    {
        return self::buildCourseRelativePrefix($course);
    }

    public static function convertDependencyFilesToAssetUrls(array $files, Course $course): array
    {
        foreach (['scripts', 'styles'] as $assetType) {
            if (empty($files[$assetType]) || !is_array($files[$assetType])) {
                continue;
            }

            foreach ($files[$assetType] as $index => $asset) {
                $path = '';

                if (is_object($asset)) {
                    $path = trim((string) ($asset->path ?? ''));
                } elseif (is_array($asset)) {
                    $path = trim((string) ($asset['path'] ?? ''));
                }

                if ('' === $path) {
                    continue;
                }

                $normalizedPath = self::normalizeRelativeStoragePath($path);

                if (null === $normalizedPath) {
                    continue;
                }

                if (!str_starts_with($normalizedPath, self::buildCourseRelativePrefix($course).'/')) {
                    $normalizedPath = self::buildCourseRelativePrefix($course).'/'.$normalizedPath;
                }

                $assetUrl = self::buildPackageAssetUrl($normalizedPath);

                if (is_object($asset)) {
                    $asset->path = $assetUrl;
                    $files[$assetType][$index] = $asset;
                } elseif (is_array($asset)) {
                    $asset['path'] = $assetUrl;
                    $files[$assetType][$index] = $asset;
                }
            }
        }

        return $files;
    }

    public static function checkPackageIntegrity(object $h5pJson, string $extractedDir): bool
    {
        $filesystem = new Filesystem();
        $packageDir = rtrim($extractedDir, '/');
        $courseStoragePath = dirname($packageDir, 2);
        $sharedLibrariesDir = $courseStoragePath.'/libraries';

        if (!$filesystem->exists($packageDir.'/h5p.json')) {
            return false;
        }

        if ($filesystem->exists($packageDir.'/content')) {
            $filesystem->mirror($packageDir.'/content', $packageDir, null, ['override' => true]);
            $filesystem->remove($packageDir.'/content');
        }

        if (!$filesystem->exists($packageDir.'/content.json')) {
            return false;
        }

        $filesystem->mkdir($sharedLibrariesDir, api_get_permissions_for_new_directories());

        foreach (($h5pJson->preloadedDependencies ?? []) as $dependency) {
            if (
                empty($dependency->machineName)
                || !isset($dependency->majorVersion)
                || !isset($dependency->minorVersion)
            ) {
                return false;
            }

            $libraryFolderName = self::buildLibraryFolderName(
                (string) $dependency->machineName,
                (int) $dependency->majorVersion,
                (int) $dependency->minorVersion
            );

            $libraryPath = self::resolveExistingLibraryPath($packageDir, $libraryFolderName);

            if (null === $libraryPath || !$filesystem->exists($libraryPath.'/library.json')) {
                return false;
            }

            $targetPath = $sharedLibrariesDir.'/'.basename($libraryPath);

            if (!$filesystem->exists($targetPath)) {
                $filesystem->rename($libraryPath, $targetPath, true);
            } else {
                $filesystem->remove($libraryPath);
            }
        }

        return true;
    }

    public static function storeH5pPackage(
        string $packagePath,
        object $h5pJson,
        Course $course,
        ?Session $session = null,
        ?array $values = null
    ): void {
        $entityManager = \Database::getManager();
        $filesystem = new Filesystem();
        $now = new \DateTime();

        $packagePath = rtrim($packagePath, '/');

        if (!$filesystem->exists($packagePath.'/h5p.json')) {
            throw new \RuntimeException('Missing h5p.json in the extracted package.');
        }

        if (!$filesystem->exists($packagePath.'/content.json')) {
            throw new \RuntimeException('Missing content.json in the extracted package.');
        }

        self::ensureCoursePersistentStorage($course);

        $relativePath = self::resolveUniquePersistentPackageRelativePath($course, basename($packagePath));
        self::writeLocalDirectoryToPersistentStorage($packagePath, $relativePath);

        $h5pImport = new H5pImport();
        $h5pImport->setName((string) ($h5pJson->title ?? basename($packagePath)));
        $h5pImport->setPath($relativePath);
        $h5pImport->setRelativePath($relativePath);
        $h5pImport->setDescription($values['description'] ?? null);
        $h5pImport->setCourse($course);
        $h5pImport->setSession($session);
        $h5pImport->setCreatedAt($now);
        $h5pImport->setModifiedAt($now);

        $entityManager->persist($h5pImport);

        $sharedLibrariesDir = self::getCourseTemporaryStoragePath($course).'/libraries';
        $resolvedMainLibrary = null;
        $localLibraryPathsToCleanup = [];

        foreach (($h5pJson->preloadedDependencies ?? []) as $libraryData) {
            $machineName = (string) $libraryData->machineName;
            $majorVersion = (int) $libraryData->majorVersion;
            $minorVersion = (int) $libraryData->minorVersion;

            $library = $entityManager->getRepository(H5pImportLibrary::class)->findOneBy([
                'machineName' => $machineName,
                'majorVersion' => $majorVersion,
                'minorVersion' => $minorVersion,
                'course' => $course,
            ]);

            $folderName = self::buildLibraryFolderName($machineName, $majorVersion, $minorVersion);
            $libraryPath = self::resolveExistingLibraryPath($sharedLibrariesDir, $folderName);

            if (null === $libraryPath) {
                throw new \RuntimeException(sprintf('Library "%s" was not found in shared storage.', $folderName));
            }

            $libraryRelativePath = self::normalizeRelativeStoragePath(
                self::buildCourseRelativePrefix($course).'/libraries/'.$folderName
            );

            if (null === $libraryRelativePath) {
                throw new \RuntimeException(sprintf('Unable to resolve the storage path for library "%s".', $folderName));
            }

            if (!self::persistentFileExists($libraryRelativePath.'/library.json')) {
                self::writeLocalDirectoryToPersistentStorage($libraryPath, $libraryRelativePath);
            }

            $libraryOwnJson = self::getJson($libraryPath.'/library.json');

            if (!$libraryOwnJson) {
                throw new \RuntimeException(sprintf('Library "%s" has an invalid library.json file.', $folderName));
            }

            if (null === $library) {
                $library = new H5pImportLibrary();
                $library->setMachineName($machineName);
                $library->setTitle((string) ($libraryOwnJson->title ?? $machineName));
                $library->setMajorVersion($majorVersion);
                $library->setMinorVersion($minorVersion);
                $library->setPatchVersion((int) ($libraryOwnJson->patchVersion ?? 0));
                $library->setRunnable((int) ($libraryOwnJson->runnable ?? 0));
                $library->setEmbedTypes(is_array($libraryOwnJson->embedTypes ?? null) ? $libraryOwnJson->embedTypes : ['div']);
                $library->setPreloadedJs(self::normalizeLibraryAssetList($libraryOwnJson->preloadedJs ?? null));
                $library->setPreloadedCss(self::normalizeLibraryAssetList($libraryOwnJson->preloadedCss ?? null));
                $library->setCourse($course);
                $library->setCreatedAt($now);
                $library->setModifiedAt($now);

                $entityManager->persist($library);
            }

            $library->setLibraryPath($libraryRelativePath);
            $h5pImport->addLibraries($library);
            $localLibraryPathsToCleanup[] = $libraryPath;

            if ((string) ($h5pJson->mainLibrary ?? '') === $machineName) {
                $resolvedMainLibrary = $library;
            }
        }

        if (null === $resolvedMainLibrary) {
            throw new \RuntimeException('The main H5P library could not be resolved.');
        }

        $h5pImport->setMainLibrary($resolvedMainLibrary);

        $entityManager->persist($h5pImport);
        $entityManager->flush();

        try {
            if ($filesystem->exists($packagePath)) {
                $filesystem->remove($packagePath);
            }
        } catch (\Throwable $e) {
            error_log('[H5pImport][store][cleanup][package] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
            error_log('[H5pImport][store][cleanup][package][path] '.$packagePath);
        }

        foreach (array_values(array_unique($localLibraryPathsToCleanup)) as $localLibraryPath) {
            try {
                if ('' !== $localLibraryPath && $filesystem->exists($localLibraryPath)) {
                    $filesystem->remove($localLibraryPath);
                }
            } catch (\Throwable $e) {
                error_log('[H5pImport][store][cleanup][library] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
                error_log('[H5pImport][store][cleanup][library][path] '.$localLibraryPath);
            }
        }

        try {
            self::cleanupTemporaryCourseStorage($course);
        } catch (\Throwable $e) {
            error_log('[H5pImport][store][cleanup][workspace] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
            error_log('[H5pImport][store][cleanup][workspace][course] '.(string) $course->getId());
        }
    }

    private static function normalizeLibraryAssetList($assets): array
    {
        if (!is_array($assets)) {
            return [];
        }

        $normalized = [];

        foreach ($assets as $asset) {
            if (is_string($asset)) {
                $asset = trim($asset);

                if ('' !== $asset) {
                    $normalized[] = $asset;
                }

                continue;
            }

            if (is_array($asset)) {
                $path = trim((string) ($asset['path'] ?? $asset['src'] ?? ''));

                if ('' !== $path) {
                    $normalized[] = $path;
                }

                continue;
            }

            if (is_object($asset)) {
                $path = trim((string) ($asset->path ?? $asset->src ?? ''));

                if ('' !== $path) {
                    $normalized[] = $path;
                }
            }
        }

        return array_values(array_unique($normalized));
    }

    public static function deleteH5pPackage(H5pImport $h5pImport): bool
    {
        $packagePath = trim((string) $h5pImport->getPath());
        $relativePackagePath = self::normalizeRelativeStoragePath((string) $h5pImport->getRelativePath());
        $h5pImportId = (int) $h5pImport->getIid();

        $entityManager = \Database::getManager();
        $connection = $entityManager->getConnection();
        $filesystem = new Filesystem();

        try {
            $connection->beginTransaction();

            $connection->executeStatement(
                'DELETE FROM plugin_h5p_import_results WHERE plugin_h5p_import_id = :id',
                ['id' => $h5pImportId]
            );

            $connection->executeStatement(
                'DELETE FROM plugin_h5p_import_rel_libraries WHERE h5p_import_id = :id',
                ['id' => $h5pImportId]
            );

            $deletedRows = $connection->executeStatement(
                'DELETE FROM plugin_h5p_import WHERE iid = :id',
                ['id' => $h5pImportId]
            );

            if ($deletedRows <= 0) {
                throw new \RuntimeException('The H5P import row was not deleted.');
            }

            $connection->commit();
        } catch (\Throwable $e) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            error_log(
                '[H5pImport][delete] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine()
            );
            error_log('[H5pImport][delete][trace] '.$e->getTraceAsString());

            return false;
        }

        if (null !== $relativePackagePath) {
            try {
                self::deletePersistentDirectory($relativePackagePath);
            } catch (\Throwable $e) {
                error_log('[H5pImport][delete][cleanup][persistent] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
                error_log('[H5pImport][delete][cleanup][persistent][path] '.$relativePackagePath);
            }
        }

        $legacyPackagePath = '';

        if ('' !== $packagePath) {
            if (self::isAbsolutePath($packagePath)) {
                $legacyPackagePath = $packagePath;
            } else {
                $legacyPackagePath = rtrim(self::getStorageBasePath(), '/').'/'.ltrim($packagePath, '/');
            }
        }

        if ('' !== $legacyPackagePath && $filesystem->exists($legacyPackagePath)) {
            try {
                $filesystem->remove($legacyPackagePath);
            } catch (\Throwable $e) {
                error_log(
                    '[H5pImport][delete][cleanup] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine()
                );
                error_log('[H5pImport][delete][cleanup][path] '.$legacyPackagePath);
            }
        }

        return true;
    }

    public static function getCoreSettings(H5pImport $h5pImport, \H5PCore $h5pCore): array
    {
        $originIsLearnpath = 'learnpath' === api_get_origin();
        $cidReq = api_get_cidreq();
        $cidQuery = '' !== $cidReq ? '&'.$cidReq : '';

        $settings = [
            'baseUrl' => api_get_path(\WEB_PATH),
            'url' => self::buildPackageAssetUrl($h5pImport->getRelativePath()),
            'postUserStatistics' => true,
            'ajax' => [
                'setFinished' => api_get_path(\WEB_PLUGIN_PATH).'H5pImport/src/ajax.php?action=set_finished&h5pId='.$h5pImport->getIid().'&learnpath='.(int) $originIsLearnpath.'&token='.\H5PCore::createToken('result').$cidQuery,
                'contentUserData' => api_get_path(\WEB_PLUGIN_PATH).'H5pImport/src/ajax.php?action=content_user_data&h5pId='.$h5pImport->getIid().'&token='.\H5PCore::createToken('content').$cidQuery,
            ],
            'saveFreq' => false,
            'l10n' => [
                'H5P' => $h5pCore->getLocalization(),
            ],
            'crossorigin' => false,
            'pluginCacheBuster' => '?0',
            'libraryUrl' => self::getCourseLibrariesAssetBasePath($h5pImport->getCourse()),
            'contents' => [],
            'loadedJs' => [],
            'loadedCss' => [],
        ];

        $loggedUser = api_get_user_info();

        if (!empty($loggedUser)) {
            $settings['user'] = [
                'name' => $loggedUser['complete_name'] ?? '',
                'mail' => $loggedUser['email'] ?? '',
            ];
        }

        return $settings;
    }

    /**
     * @return array<string, array<int, string>>|false
     */
    public static function getCoreAssets()
    {
        $assets = [
            'css' => [],
            'js' => [],
        ];

        $projectRoot = dirname(__DIR__, 4);
        $coreBasePath = $projectRoot.'/vendor/h5p/h5p-core';

        foreach (\H5PCore::$styles as $style) {
            $relativeAssetPath = ltrim((string) $style, '/');
            $absoluteAssetPath = $coreBasePath.'/'.$relativeAssetPath;

            if (!is_file($absoluteAssetPath)) {
                return false;
            }

            $assets['css'][] = api_get_path(\WEB_PLUGIN_PATH).'H5pImport/core_asset.php?'.http_build_query([
                    'path' => $relativeAssetPath,
                ]);
        }

        foreach (\H5PCore::$scripts as $script) {
            $relativeAssetPath = ltrim((string) $script, '/');
            $absoluteAssetPath = $coreBasePath.'/'.$relativeAssetPath;

            if (!is_file($absoluteAssetPath)) {
                return false;
            }

            $assets['js'][] = api_get_path(\WEB_PLUGIN_PATH).'H5pImport/core_asset.php?'.http_build_query([
                    'path' => $relativeAssetPath,
                ]);
        }

        return $assets;
    }

    public function getContentSettings(array $h5pNode): array
    {
        $params = $h5pNode['params'] ?? '{}';

        if (!is_string($params)) {
            $encoded = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $params = false === $encoded ? '{}' : $encoded;
        }

        $library = $h5pNode['library'] ?? [];

        return [
            'library' => ($library['machineName'] ?? '').' '.($library['majorVersion'] ?? 0).'.'.($library['minorVersion'] ?? 0),
            'jsonContent' => $params,
            'filtered' => $params,
            'displayOptions' => $h5pNode['display_options'] ?? [],
            'metadata' => $h5pNode['metadata'] ?? [],
        ];
    }

    public static function h5pDependenciesToLibraryList(array $dependencies): array
    {
        $libraryList = [];

        foreach ($dependencies as $dependency) {
            $libraryList[$dependency['machineName']] = [
                'majorVersion' => $dependency['majorVersion'],
                'minorVersion' => $dependency['minorVersion'],
            ];
        }

        return $libraryList;
    }

    private static function buildLibraryFolderName(string $machineName, int $majorVersion, int $minorVersion): string
    {
        return $machineName.'-'.$majorVersion.'.'.$minorVersion;
    }

    private static function resolveExistingLibraryPath(string $basePath, string $expectedFolderName): ?string
    {
        $filesystem = new Filesystem();

        $rawPath = rtrim($basePath, '/').'/'.$expectedFolderName;
        if ($filesystem->exists($rawPath)) {
            return $rawPath;
        }

        $safePath = rtrim($basePath, '/').'/'.api_replace_dangerous_char($expectedFolderName);
        if ($filesystem->exists($safePath)) {
            return $safePath;
        }

        return null;
    }
}
