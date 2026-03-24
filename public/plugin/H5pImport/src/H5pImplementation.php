<?php

// For licensing terms, see /license.txt

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportLibrary;

class H5pImplementation implements \H5PFrameworkInterface
{
    private H5pImport $h5pImport;
    private $h5pImportLibraries;
    private array $messages = [
        'error' => [],
        'info' => [],
    ];
    private array $contentHubMetadata = [];
    private array $contentHubChecked = [];

    public function __construct(H5pImport $h5pImport)
    {
        $this->h5pImport = $h5pImport;
        $this->h5pImportLibraries = $h5pImport->getLibraries();
    }

    public function setContentHubMetadataChecked($time, $lang = 'en')
    {
        $this->contentHubChecked[$lang] = $time;

        return true;
    }

    public function getContentHubMetadataChecked($lang = 'en')
    {
        return $this->contentHubChecked[$lang] ?? null;
    }

    public function getContentHubMetadataCache($lang = 'en')
    {
        return $this->contentHubMetadata[$lang] ?? null;
    }

    public function replaceContentHubMetadataCache($metadata, $lang)
    {
        $this->contentHubMetadata[$lang] = $metadata;

        return true;
    }

    public function getPlatformInfo()
    {
        return [
            'name' => 'Chamilo',
            'version' => api_get_setting('platform.version'),
            'h5pVersion' => api_get_setting('platform.version'),
        ];
    }

    public function fetchExternalData(
        $url,
        $data = null,
        $blocking = true,
        $stream = null,
        $fullData = false,
        $headers = [],
        $files = [],
        $method = 'POST'
    ) {
        return false;
    }

    public function setLibraryTutorialUrl($machineName, $tutorialUrl)
    {
        return true;
    }

    public function setErrorMessage($message, $code = null)
    {
        if (null !== $message && '' !== $message) {
            $this->messages['error'][] = $message;
        }
    }

    public function setInfoMessage($message)
    {
        if (null !== $message && '' !== $message) {
            $this->messages['info'][] = $message;
        }
    }

    public function getMessages($type)
    {
        return $this->messages[$type] ?? [];
    }

    public function t($message, $replacements = [])
    {
        $translated = get_lang($message);
        if (empty($translated) || $translated === $message) {
            $translated = $message;
        }

        return $replacements ? strtr($translated, $replacements) : $translated;
    }

    public function getLibraryFileUrl($libraryFolderName, $fileName)
    {
        $course = $this->h5pImport->getCourse();

        return api_get_path(WEB_COURSE_PATH).$course->getDirectory().'/h5p/libraries/'.$libraryFolderName.'/'.$fileName;
    }

    public function getUploadedH5pFolderPath()
    {
        $path = api_get_path(SYS_ARCHIVE_PATH).'h5pimport_tmp';

        if (!is_dir($path)) {
            @mkdir($path, api_get_permissions_for_new_directories(), true);
        }

        return $path;
    }

    public function getUploadedH5pPath()
    {
        return $this->getUploadedH5pFolderPath().'/upload.h5p';
    }

    public function loadAddons()
    {
        $addons = [];
        $sql = '
            SELECT l1.machine_name, l1.major_version, l1.minor_version, l1.patch_version,
                   l1.iid, l1.preloaded_js, l1.preloaded_css
            FROM plugin_h5p_import_library AS l1
            LEFT JOIN plugin_h5p_import_library AS l2
              ON l1.machine_name = l2.machine_name
             AND (l1.major_version < l2.major_version
               OR (l1.major_version = l2.major_version AND l1.minor_version < l2.minor_version))
            WHERE l2.machine_name IS NULL
        ';

        $result = \Database::query($sql);
        while ($row = \Database::fetch_array($result)) {
            $addons[] = \H5PCore::snakeToCamel($row);
        }

        return $addons;
    }

    public function getLibraryConfig($libraries = null)
    {
        return [];
    }

    public function loadLibraries()
    {
        $libraries = [];

        /** @var H5pImportLibrary $library */
        foreach ($this->h5pImportLibraries as $library) {
            $libraries[] = [
                'libraryId' => $library->getIid(),
                'title' => $library->getTitle(),
                'machineName' => $library->getMachineName(),
                'majorVersion' => $library->getMajorVersion(),
                'minorVersion' => $library->getMinorVersion(),
                'patchVersion' => $library->getPatchVersion(),
                'runnable' => $library->getRunnable(),
                'preloadedJs' => $library->getPreloadedJsFormatted(),
                'preloadedCss' => $library->getPreloadedCssFormatted(),
            ];
        }

        return $libraries;
    }

    public function getAdminUrl()
    {
        return api_get_path(WEB_PATH).'main/admin/settings.php?category=Plugins';
    }

    public function getLibraryId($machineName, $majorVersion = null, $minorVersion = null)
    {
        /** @var H5pImportLibrary $library */
        foreach ($this->h5pImportLibraries as $library) {
            if (
                $library->getMachineName() === $machineName
                && (null === $majorVersion || $library->getMajorVersion() === (int) $majorVersion)
                && (null === $minorVersion || $library->getMinorVersion() === (int) $minorVersion)
            ) {
                return $library->getIid();
            }
        }

        return false;
    }

    public function getWhitelist($isLibrary, $defaultContentWhitelist, $defaultLibraryWhitelist)
    {
        return $isLibrary ? $defaultLibraryWhitelist : $defaultContentWhitelist;
    }

    public function isPatchedLibrary($library)
    {
        return false;
    }

    public function isInDevMode()
    {
        return false;
    }

    public function mayUpdateLibraries()
    {
        return true;
    }

    public function saveLibraryData(&$libraryData, $new = true)
    {
        return false;
    }

    public function insertContent($content, $contentMainId = null)
    {
        return false;
    }

    public function updateContent($content, $contentMainId = null)
    {
        return false;
    }

    public function resetContentUserData($contentId)
    {
        return true;
    }

    public function saveLibraryDependencies($libraryId, $dependencies, $dependencyType)
    {
        return true;
    }

    public function copyLibraryUsage($contentId, $copyFromId, $contentMainId = null)
    {
        return true;
    }

    public function deleteContentData($contentId)
    {
        return true;
    }

    public function deleteLibraryUsage($contentId)
    {
        return true;
    }

    public function saveLibraryUsage($contentId, $librariesInUse)
    {
        return true;
    }

    public function getLibraryUsage($libraryId, $skipContent = false)
    {
        return false;
    }

    public function loadLibrary($machineName, $majorVersion, $minorVersion)
    {
        if (!$this->h5pImportLibraries) {
            return false;
        }

        $foundLibrary = $this->h5pImportLibraries->filter(
            static function (H5pImportLibrary $library) use ($machineName, $majorVersion, $minorVersion) {
                return false !== $library->getLibraryByMachineNameAndVersions($machineName, (int) $majorVersion, (int) $minorVersion);
            }
        )->first();

        if (!$foundLibrary instanceof H5pImportLibrary) {
            return false;
        }

        $embedTypes = $foundLibrary->getEmbedTypes() ?? ['div'];

        return [
            'libraryId' => $foundLibrary->getIid(),
            'title' => $foundLibrary->getTitle(),
            'machineName' => $foundLibrary->getMachineName(),
            'majorVersion' => $foundLibrary->getMajorVersion(),
            'minorVersion' => $foundLibrary->getMinorVersion(),
            'patchVersion' => $foundLibrary->getPatchVersion(),
            'runnable' => $foundLibrary->getRunnable(),
            'preloadedJs' => $foundLibrary->getPreloadedJsFormatted(),
            'preloadedCss' => $foundLibrary->getPreloadedCssFormatted(),
            'embedTypes' => $embedTypes,
            'fullscreen' => 0,
        ];
    }

    public function loadLibrarySemantics($machineName, $majorVersion, $minorVersion)
    {
        /** @var H5pImportLibrary $library */
        foreach ($this->h5pImportLibraries as $library) {
            if (false !== $library->getLibraryByMachineNameAndVersions($machineName, (int) $majorVersion, (int) $minorVersion)) {
                $semantics = H5pPackageTools::getJson($library->getLibraryPath().'/semantics.json', true);

                return $semantics ?: false;
            }
        }

        return false;
    }

    public function alterLibrarySemantics(&$semantics, $machineName, $majorVersion, $minorVersion)
    {
        return;
    }

    public function deleteLibraryDependencies($libraryId)
    {
        return true;
    }

    public function lockDependencyStorage()
    {
        return true;
    }

    public function unlockDependencyStorage()
    {
        return true;
    }

    public function deleteLibrary($library)
    {
        return false;
    }

    public function loadContent($id): array
    {
        $packagePath = rtrim($this->h5pImport->getPath(), '/');
        $contentJson = H5pPackageTools::getJson($packagePath.'/content.json');
        if (!$contentJson) {
            $contentJson = H5pPackageTools::getJson($packagePath.'/content/content.json');
        }

        $h5pJson = H5pPackageTools::getJson($packagePath.'/h5p.json');
        $mainLibrary = $this->h5pImport->getMainLibrary();

        if (!$contentJson || !$h5pJson || null === $mainLibrary) {
            return [];
        }

        $embedTypes = $mainLibrary->getEmbedTypes() ?? ['div'];

        return [
            'id' => $this->h5pImport->getIid(),
            'contentId' => $this->h5pImport->getIid(),
            'mainId' => $this->h5pImport->getIid(),
            'slug' => (string) $this->h5pImport->getIid(),
            'params' => json_encode($contentJson),
            'embedType' => implode(',', $embedTypes),
            'title' => $this->h5pImport->getName(),
            'language' => $h5pJson->language ?? 'en',
            'libraryId' => $mainLibrary->getIid(),
            'libraryName' => $mainLibrary->getMachineName(),
            'libraryMajorVersion' => $mainLibrary->getMajorVersion(),
            'libraryMinorVersion' => $mainLibrary->getMinorVersion(),
            'libraryEmbedTypes' => $mainLibrary->getEmbedTypesFormatted(),
            'libraryFullscreen' => 0,
            'library' => [
                'libraryId' => $mainLibrary->getIid(),
                'machineName' => $mainLibrary->getMachineName(),
                'majorVersion' => $mainLibrary->getMajorVersion(),
                'minorVersion' => $mainLibrary->getMinorVersion(),
                'embedTypes' => $embedTypes,
                'fullscreen' => 0,
            ],
            'url' => api_get_path(WEB_PLUGIN_PATH).'H5pImport/view.php?id='.$this->h5pImport->getIid().'&'.api_get_cidreq(),
            'metadata' => [
                'title' => $this->h5pImport->getName(),
                'license' => $h5pJson->license ?? null,
            ],
        ];
    }

    public function loadContentDependencies($id, $type = null): array
    {
        $dependencies = [];

        /** @var H5pImportLibrary $library */
        foreach ($this->h5pImportLibraries as $library) {
            $dependencies[] = [
                'libraryId' => $library->getIid(),
                'machineName' => $library->getMachineName(),
                'majorVersion' => $library->getMajorVersion(),
                'minorVersion' => $library->getMinorVersion(),
                'patchVersion' => $library->getPatchVersion(),
                'preloadedJs' => $library->getPreloadedJsFormatted(),
                'preloadedCss' => $library->getPreloadedCssFormatted(),
            ];
        }

        return $dependencies;
    }

    public function getOption($name, $default = null)
    {
        $plugin = \H5pImportPlugin::create();
        $value = $plugin->get($name);

        return null === $value || '' === (string) $value ? $default : $value;
    }

    public function setOption($name, $value)
    {
        return true;
    }

    public function updateContentFields($id, $fields)
    {
        return true;
    }

    public function clearFilteredParameters($libraryIds)
    {
        return true;
    }

    public function getNumNotFiltered()
    {
        return 0;
    }

    public function getNumContent($libraryId, $skip = null)
    {
        return 0;
    }

    public function isContentSlugAvailable($slug)
    {
        return true;
    }

    public function getLibraryStats($type)
    {
        return [];
    }

    public function getNumAuthors()
    {
        return 0;
    }

    public function saveCachedAssets($key, $libraries)
    {
        return true;
    }

    public function deleteCachedAssets($libraryId)
    {
        return true;
    }

    public function getLibraryContentCount()
    {
        return 0;
    }

    public function afterExportCreated($content, $filename)
    {
        return true;
    }

    public function hasPermission($permission, $id = null)
    {
        return true;
    }

    public function replaceContentTypeCache($contentTypeCache)
    {
        return true;
    }

    public function libraryHasUpgrade($library)
    {
        return false;
    }
}
