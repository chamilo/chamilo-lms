<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportLibrary;
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

    public static function buildCourseRelativePrefix(Course $course): string
    {
        return 'course_'.$course->getId();
    }

    public static function getCourseStoragePath(Course $course): string
    {
        $path = rtrim(self::getStorageBasePath(), '/').'/'.self::buildCourseRelativePrefix($course);

        $filesystem = new Filesystem();
        $filesystem->mkdir($path, api_get_permissions_for_new_directories());
        $filesystem->mkdir($path.'/content', api_get_permissions_for_new_directories());
        $filesystem->mkdir($path.'/libraries', api_get_permissions_for_new_directories());

        return $path;
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

        return api_get_path(\WEB_PLUGIN_PATH).'H5pImport/package_asset.php?path='.$relativePath;
    }

    public static function getCourseLibrariesAssetBaseUrl(Course $course): string
    {
        return self::buildPackageAssetUrl(self::buildCourseRelativePrefix($course));
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

        $relativePath = self::normalizeRelativeStoragePath(
            self::buildCourseRelativePrefix($course).'/content/'.basename($packagePath)
        );

        if (null === $relativePath) {
            throw new \RuntimeException('Unable to resolve the package storage path.');
        }

        $h5pImport = new H5pImport();
        $h5pImport->setName((string) ($h5pJson->title ?? basename($packagePath)));
        $h5pImport->setPath($packagePath);
        $h5pImport->setRelativePath($relativePath);
        $h5pImport->setDescription($values['description'] ?? null);
        $h5pImport->setCourse($course);
        $h5pImport->setSession($session);
        $h5pImport->setCreatedAt($now);
        $h5pImport->setModifiedAt($now);

        $entityManager->persist($h5pImport);

        $sharedLibrariesDir = self::getCourseStoragePath($course).'/libraries';
        $resolvedMainLibrary = null;

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

            if (null === $library) {
                $libraryOwnJson = self::getJson($libraryPath.'/library.json');

                if (!$libraryOwnJson) {
                    throw new \RuntimeException(sprintf('Library "%s" has an invalid library.json file.', $folderName));
                }

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
                $library->setLibraryPath($libraryPath);
                $library->setCourse($course);
                $library->setCreatedAt($now);
                $library->setModifiedAt($now);

                $entityManager->persist($library);
            }

            $h5pImport->addLibraries($library);

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
        $packagePath = $h5pImport->getPath();
        $h5pImportId = (int) $h5pImport->getIid();

        $entityManager = \Database::getManager();
        $connection = $entityManager->getConnection();
        $filesystem = new Filesystem();

        $connection->beginTransaction();

        try {
            $connection->executeStatement(
                'DELETE FROM plugin_h5p_import_results WHERE plugin_h5p_import_id = :id',
                ['id' => $h5pImportId]
            );

            $connection->executeStatement(
                'DELETE FROM plugin_h5p_import_rel_libraries WHERE h5p_import_id = :id',
                ['id' => $h5pImportId]
            );

            $managed = $entityManager->find(H5pImport::class, $h5pImportId);

            if (null !== $managed) {
                $entityManager->remove($managed);
                $entityManager->flush();
            }

            $connection->commit();
        } catch (\Throwable $e) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            return false;
        }

        if ($filesystem->exists($packagePath)) {
            try {
                $filesystem->remove($packagePath);
            } catch (\Throwable $e) {
                return false;
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
            'libraryUrl' => self::getCourseLibrariesAssetBaseUrl($h5pImport->getCourse()),
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
