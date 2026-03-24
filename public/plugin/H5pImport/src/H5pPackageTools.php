<?php

// For licensing terms, see /license.txt

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
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

    public static function checkPackageIntegrity(object $h5pJson, string $extractedDir): bool
    {
        $filesystem = new Filesystem();
        $packageDir = rtrim($extractedDir, '/');
        $h5pDir = dirname($packageDir, 2);
        $sharedLibrariesDir = $h5pDir.'/libraries';

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

        if (!$filesystem->exists($sharedLibrariesDir)) {
            $filesystem->mkdir($sharedLibrariesDir, api_get_permissions_for_new_directories());
        }

        $preloadedDependencies = $h5pJson->preloadedDependencies ?? [];
        foreach ($preloadedDependencies as $dependency) {
            if (
                empty($dependency->machineName)
                || !isset($dependency->majorVersion)
                || !isset($dependency->minorVersion)
            ) {
                return false;
            }

            $libraryFolderName = api_replace_dangerous_char(
                $dependency->machineName.'-'.$dependency->majorVersion.'.'.$dependency->minorVersion
            );
            $libraryPath = $packageDir.'/'.$libraryFolderName;

            if (!$filesystem->exists($libraryPath) || !$filesystem->exists($libraryPath.'/library.json')) {
                return false;
            }

            $targetPath = $sharedLibrariesDir.'/'.$libraryFolderName;
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
        $h5pDir = dirname($packagePath, 2);
        $sharedLibrariesDir = $h5pDir.'/libraries';

        $mainLibraryName = (string) ($h5pJson->mainLibrary ?? '');
        $relativePath = api_get_path(REL_COURSE_PATH).$course->getDirectory().'/h5p/';

        $h5pImport = new H5pImport();
        $h5pImport->setName((string) ($h5pJson->title ?? basename($packagePath)));
        $h5pImport->setPath(rtrim($packagePath, '/'));
        $h5pImport->setDescription($values['description'] ?? null);
        $h5pImport->setRelativePath($relativePath);
        $h5pImport->setCourse($course);
        $h5pImport->setSession($session);
        $entityManager->persist($h5pImport);

        $libraries = $h5pJson->preloadedDependencies ?? [];
        foreach ($libraries as $libraryData) {
            $library = $entityManager->getRepository(H5pImportLibrary::class)->findOneBy([
                'machineName' => $libraryData->machineName,
                'majorVersion' => (int) $libraryData->majorVersion,
                'minorVersion' => (int) $libraryData->minorVersion,
                'course' => $course,
            ]);

            if (null === $library) {
                $folderName = api_replace_dangerous_char(
                    $libraryData->machineName.'-'.$libraryData->majorVersion.'.'.$libraryData->minorVersion
                );
                $libraryOwnJson = self::getJson($sharedLibrariesDir.'/'.$folderName.'/library.json');
                if (!$libraryOwnJson) {
                    continue;
                }

                $library = new H5pImportLibrary();
                $library->setMachineName((string) $libraryData->machineName);
                $library->setTitle((string) ($libraryOwnJson->title ?? $libraryData->machineName));
                $library->setMajorVersion((int) $libraryData->majorVersion);
                $library->setMinorVersion((int) $libraryData->minorVersion);
                $library->setPatchVersion((int) ($libraryOwnJson->patchVersion ?? 0));
                $library->setRunnable((int) ($libraryOwnJson->runnable ?? 0));
                $library->setEmbedTypes(is_array($libraryOwnJson->embedTypes ?? null) ? $libraryOwnJson->embedTypes : ['div']);
                $library->setPreloadedJs(is_array($libraryOwnJson->preloadedJs ?? null) ? $libraryOwnJson->preloadedJs : []);
                $library->setPreloadedCss(is_array($libraryOwnJson->preloadedCss ?? null) ? $libraryOwnJson->preloadedCss : []);
                $library->setLibraryPath($sharedLibrariesDir.'/'.$folderName);
                $library->setCourse($course);
                $entityManager->persist($library);
            }

            $h5pImport->addLibraries($library);

            if ($mainLibraryName === $libraryData->machineName) {
                $h5pImport->setMainLibrary($library);
            }
        }

        $entityManager->persist($h5pImport);
        $entityManager->flush();
    }

    public static function deleteH5pPackage(H5pImport $h5pImport): bool
    {
        $packagePath = $h5pImport->getPath();
        $entityManager = \Database::getManager();
        $entityManager->remove($h5pImport);
        $entityManager->flush();

        $filesystem = new Filesystem();
        if ($filesystem->exists($packagePath)) {
            try {
                $filesystem->remove($packagePath);
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }

    public static function getCoreSettings(H5pImport $h5pImport, \H5PCore $h5pCore): array
    {
        $originIsLearnpath = 'learnpath' === api_get_origin();
        $course = $h5pImport->getCourse();
        $libraryBaseUrl = api_get_path(WEB_COURSE_PATH).$course->getDirectory().'/h5p/libraries';
        $cidReq = api_get_cidreq();
        $cidQuery = '' !== $cidReq ? '&'.$cidReq : '';

        $settings = [
            'baseUrl' => api_get_path(WEB_PATH),
            'url' => $h5pImport->getRelativePath(),
            'postUserStatistics' => true,
            'ajax' => [
                'setFinished' => api_get_path(WEB_PLUGIN_PATH).'H5pImport/src/ajax.php?action=set_finished&h5pId='.$h5pImport->getIid().'&learnpath='.(int) $originIsLearnpath.'&token='.\H5PCore::createToken('result').$cidQuery,
                'contentUserData' => api_get_path(WEB_PLUGIN_PATH).'H5pImport/src/ajax.php?action=content_user_data&h5pId='.$h5pImport->getIid().'&token='.\H5PCore::createToken('content').$cidQuery,
            ],
            'saveFreq' => false,
            'l10n' => [
                'H5P' => $h5pCore->getLocalization(),
            ],
            'crossorigin' => false,
            'pluginCacheBuster' => '?0',
            'libraryUrl' => $libraryBaseUrl,
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

        foreach (\H5PCore::$styles as $style) {
            $assetPath = 'vendor/h5p/h5p-core/'.$style;
            if (!file_exists(api_get_path(SYS_PATH).$assetPath)) {
                return false;
            }

            $assets['css'][] = api_get_path(WEB_PATH).$assetPath;
        }

        foreach (\H5PCore::$scripts as $script) {
            $assetPath = 'vendor/h5p/h5p-core/'.$script;
            if (!file_exists(api_get_path(SYS_PATH).$assetPath)) {
                return false;
            }

            $assets['js'][] = api_get_path(WEB_PATH).$assetPath;
        }

        return $assets;
    }

    /**
     * @param mixed $h5pNode
     */
    public static function getContentSettings($h5pNode, \H5PCore $h5pCore): array
    {
        $filtered = $h5pCore->filterParameters($h5pNode);
        $displayOptions = [
            'frame' => self::getPluginBooleanSetting('frame', true),
            'embed' => self::getPluginBooleanSetting('embed', true),
            'copyright' => self::getPluginBooleanSetting('copyright', true),
            'icon' => self::getPluginBooleanSetting('icon', true),
        ];

        return [
            'library' => \H5PCore::libraryToString($h5pNode['library']),
            'jsonContent' => $h5pNode['params'],
            'fullScreen' => $h5pNode['library']['fullscreen'] ?? 0,
            'exportUrl' => '',
            'language' => $h5pNode['language'] ?? 'en',
            'filtered' => is_string($filtered) && '' !== $filtered ? $filtered : $h5pNode['params'],
            'embedCode' => '',
            'resizeCode' => '',
            'mainId' => $h5pNode['mainId'],
            'url' => $h5pNode['url'],
            'contentUserData' => [['state' => '{}']],
            'displayOptions' => $displayOptions,
            'metadata' => $h5pNode['metadata'],
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

    private static function getPluginBooleanSetting(string $name, bool $default = true): bool
    {
        $plugin = \H5pImportPlugin::create();
        $value = $plugin->get($name);

        if (null === $value || '' === $value) {
            return $default;
        }

        return in_array($value, [true, 1, '1', 'true', 'yes', 'on'], true);
    }
}
