<?php

// For licensing terms, see /license.txt

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImportLibrary;
use Database;
use H5PCore;
use Symfony\Component\Filesystem\Filesystem;

class H5pPackageTools
{
    /**
     * Help read JSON from the archive.
     *
     * @return mixed JSON content if valid or FALSE for invalid
     */
    public static function getJson(string $file, bool $assoc = false)
    {
        $fs = new Filesystem();
        $json = false;

        if ($fs->exists($file)) {
            $contents = file_get_contents($file);

            // Decode the data
            $json = json_decode($contents, $assoc);
            if (null === $json) {
                // JSON cannot be decoded or the recursion limit has been reached.
                return false;
            }
        }

        return $json;
    }

    /**
     * Checks the integrity of an H5P package by verifying the existence of libraries
     * and moves them to the "libraries" directory.
     *
     * @param object $h5pJson      the H5P JSON object
     * @param string $extractedDir the path to the extracted directory
     *
     * @return bool true if the package integrity is valid, false otherwise
     */
    public static function checkPackageIntegrity(object $h5pJson, string $extractedDir): bool
    {
        $filesystem = new Filesystem();
        $h5pDir = dirname($extractedDir, 2);
        $sharedLibrariesDir = $h5pDir.'/libraries';

        // Move 'content' directory one level back (H5P specification)
        $filesystem->mirror($extractedDir.'/content', $extractedDir, null, ['override' => true]);
        $filesystem->remove($extractedDir.'/content');
        // Get the list of preloaded dependencies
        $preloadedDependencies = $h5pJson->preloadedDependencies;

        // Check the existence of each library in the extracted directory
        foreach ($preloadedDependencies as $dependency) {
            $libraryName = $dependency->machineName;
            $majorVersion = $dependency->majorVersion;
            $minorVersion = $dependency->minorVersion;

            $libraryFolderName = api_replace_dangerous_char($libraryName.'-'.$majorVersion.'.'.$minorVersion);
            $libraryPath = $extractedDir.'/'.$libraryFolderName;

            // Check if the library folder exists
            if (!$filesystem->exists($libraryPath)) {
                return false;
            }

            // Move the entire folder to the "libraries" directory
            $targetPath = $sharedLibrariesDir.'/'.$libraryFolderName;

            $filesystem->rename($libraryPath, $targetPath, true);
        }

        return true;
    }

    /**
     * Stores the H5P package information in the database.
     *
     * @param string       $packagePath the path to the H5P package file
     * @param object       $h5pJson     the parsed H5P JSON object
     * @param Course       $course      the course entity related to the package
     * @param null|Session $session     the session entity related to the package
     * @param null|array   $values      the advance options in upload form
     */
    public static function storeH5pPackage(
        string $packagePath,
        object $h5pJson,
        Course $course,
        Session $session = null,
        array $values = null
    ) {
        $entityManager = \Database::getManager();
        // Go back 2 directories
        $h5pDir = dirname($packagePath, 2);
        $sharedLibrariesDir = $h5pDir.'/libraries';

        $mainLibraryName = $h5pJson->mainLibrary;
        $relativePath = api_get_path(REL_COURSE_PATH).$course->getDirectory().'/h5p/';

        $h5pImport = new H5pImport();
        $h5pImport->setName($h5pJson->title);
        $h5pImport->setPath($packagePath);
        if ($values) {
            $h5pImport->setDescription($values['description']);
        }
        $h5pImport->setRelativePath($relativePath);
        $h5pImport->setCourse($course);
        $h5pImport->setSession($session);
        $entityManager->persist($h5pImport);

        $libraries = $h5pJson->preloadedDependencies;

        foreach ($libraries as $libraryData) {
            $library = $entityManager
                ->getRepository(H5pImportLibrary::class)
                ->findOneBy(
                    [
                        'machineName' => $libraryData->machineName,
                        'majorVersion' => $libraryData->majorVersion,
                        'minorVersion' => $libraryData->minorVersion,
                        'course' => $course,
                    ]
                )
            ;

            if (null === $library) {
                $auxFullName = $libraryData->machineName.'-'.$libraryData->majorVersion.'.'.$libraryData->minorVersion;
                $libraryOwnJson = self::getJson($sharedLibrariesDir.'/'.$auxFullName.'/library.json');

                $library = new H5pImportLibrary();
                $library->setMachineName($libraryData->machineName);
                $library->setTitle($libraryOwnJson->title);
                $library->setMajorVersion($libraryData->majorVersion);
                $library->setMinorVersion($libraryData->minorVersion);
                $library->setPatchVersion($libraryOwnJson->patchVersion);
                $library->setRunnable($libraryOwnJson->runnable);
                $library->setEmbedTypes($libraryOwnJson->embedTypes);
                $library->setPreloadedJs($libraryOwnJson->preloadedJs);
                $library->setPreloadedCss($libraryOwnJson->preloadedCss);
                $library->setLibraryPath($sharedLibrariesDir.'/'.$auxFullName);
                $library->setCourse($course);
                $entityManager->persist($library);
                $entityManager->flush();
            }

            $h5pImport->addLibraries($library);
            if ($mainLibraryName === $libraryData->machineName) {
                $h5pImport->setMainLibrary($library);
            }
            $entityManager->persist($h5pImport);
            $entityManager->flush();
        }
    }

    /**
     * Deletes an H5P package from the database and the disk.
     *
     * @param H5pImport $h5pImport the H5P import entity representing the package to delete
     *
     * @return bool true if the package was successfully deleted, false otherwise
     */
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
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get core settings for H5P content.
     *
     * @param H5pImport $h5pImport the H5pImport object
     * @param \H5PCore  $h5pCore   the H5PCore object
     *
     * @return array the core settings for H5P content
     */
    public static function getCoreSettings(H5pImport $h5pImport, \H5PCore $h5pCore): array
    {
        $originIsLearnpath = 'learnpath' === api_get_origin();

        $settings = [
            'baseUrl' => api_get_path(WEB_PATH),
            'url' => $h5pImport->getRelativePath(),
            'postUserStatistics' => true,
            'ajax' => [
                'setFinished' => api_get_path(WEB_PLUGIN_PATH).'h5pimport/src/ajax.php?action=set_finished&h5pId='.$h5pImport->getIid().'&learnpath='.$originIsLearnpath.'&token='.\H5PCore::createToken('result'),
                'contentUserData' => api_get_path(WEB_PLUGIN_PATH).'h5pimport/src/ajax.php?action=content_user_data&h5pId='.$h5pImport->getIid().'&token='.\H5PCore::createToken('content'),
            ],
            'saveFreq' => false,
            'l10n' => [
                'H5P' => $h5pCore->getLocalization(),
            ],
            //            'hubIsEnabled' => variable_get('h5p_hub_is_enabled', TRUE) ? TRUE : FALSE,
            'crossorigin' => false,
            //            'crossoriginCacheBuster' => variable_get('h5p_crossorigin_cache_buster', NULL),
            //            'libraryConfig' => $core->h5pF->getLibraryConfig(),
            'pluginCacheBuster' => '?0',
            'libraryUrl' => $h5pImport->getMainLibrary()->getLibraryPath().'/js',
        ];

        $loggedUser = api_get_user_info();
        if ($loggedUser) {
            $settings['user'] = [
                'name' => $loggedUser['complete_name'],
                'mail' => $loggedUser['email'],
            ];
        }

        return $settings;
    }

    /**
     * Get the core assets.
     *
     * @return array[]|bool an array containing CSS and JS assets or false if some core assets missing
     */
    public static function getCoreAssets()
    {
        $assets = [
            'css' => [],
            'js' => [],
        ];

        // Add CSS assets
        foreach (\H5PCore::$styles as $style) {
            $auxAssetPath = 'vendor/h5p/h5p-core/'.$style;
            $assets['css'][] = api_get_path(WEB_PATH).$auxAssetPath;
            if (!file_exists(api_get_path(SYS_PATH).$auxAssetPath)) {
                return false;
            }
        }

        // Add JS assets
        foreach (\H5PCore::$scripts as $script) {
            $auxAssetPath = 'vendor/h5p/h5p-core/'.$script;
            $auxUrl = api_get_path(WEB_PATH).$auxAssetPath;
            $assets['js'][] = $auxUrl;
            if (!file_exists(api_get_path(SYS_PATH).$auxAssetPath)) {
                return false;
            }
        }

        return $assets;
    }

    /**
     * Return the content body for the H5PIntegration javascript object.
     *
     * @param mixed $h5pNode
     */
    public static function getContentSettings($h5pNode, \H5PCore $h5pCore): array
    {
        $filtered = $h5pCore->filterParameters($h5pNode);
        $contentUserData = [
            0 => [
                'state' => '{}',
            ],
        ];

        // ToDo Use $h5pCore->getDisplayOptionsForView() function
        $displayOptions = [
            'frame' => api_get_course_plugin_setting('h5pimport', 'frame'),
            'embed' => api_get_course_plugin_setting('h5pimport', 'embed'),
            'copyright' => api_get_course_plugin_setting('h5pimport', 'copyright'),
            'icon' => api_get_course_plugin_setting('h5pimport', 'icon'),
        ];

        return [
            'library' => \H5PCore::libraryToString($h5pNode['library']),
            'jsonContent' => $h5pNode['params'],
            'fullScreen' => $h5pNode['library']['fullscreen'],
            'exportUrl' => '',
            'language' => 'en',
            'filtered' => $filtered,
            'embedCode' => '<iframe src="'.api_get_course_url().'h5p/embed/'.$h5pNode['mainId'].'" width=":w" height=":h" frameborder="0" allowfullscreen="allowfullscreen" allow="geolocation *; microphone *; camera *; midi *; encrypted-media *" title="'.$h5pNode['title'].'"></iframe>',
            'resizeCode' => '',
            'mainId' => $h5pNode['mainId'],
            'url' => $h5pNode['url'],
            'contentUserData' => $contentUserData,
            'displayOptions' => $displayOptions,
            'metadata' => $h5pNode['metadata'],
        ];
    }

    /**
     * Convert H5P dependencies to a library list.
     *
     * @param array $dependencies the H5P dependencies
     *
     * @return array the library list with machine names as keys and version information as values
     */
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
}
