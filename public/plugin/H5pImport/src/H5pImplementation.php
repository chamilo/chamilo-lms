<?php

// For licensing terms, see /license.txt

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImportLibrary;

class H5pImplementation implements \H5PFrameworkInterface
{
    private $h5pImport;
    private $h5pImportLibraries;

    public function __construct(H5pImport $h5pImport)
    {
        $this->h5pImport = $h5pImport;
        $this->h5pImportLibraries = $h5pImport->getLibraries();
    }

    public function getPlatformInfo()
    {
        // TODO: Implement getPlatformInfo() method.
    }

    public function fetchExternalData($url, $data = null, $blocking = true, $stream = null)
    {
        // TODO: Implement fetchExternalData() method.
    }

    public function setLibraryTutorialUrl($machineName, $tutorialUrl)
    {
        // TODO: Implement setLibraryTutorialUrl() method.
    }

    public function setErrorMessage($message, $code = null)
    {
        // TODO: Implement setErrorMessage() method.
    }

    public function setInfoMessage($message)
    {
        // TODO: Implement setInfoMessage() method.
    }

    public function getMessages($type)
    {
        // TODO: Implement getMessages() method.
    }

    public function t($message, $replacements = [])
    {
        return get_lang($message);
    }

    public function getLibraryFileUrl($libraryFolderName, $fileName)
    {
        // TODO: Implement getLibraryFileUrl() method.
    }

    public function getUploadedH5pFolderPath()
    {
        // TODO: Implement getUploadedH5pFolderPath() method.
    }

    public function getUploadedH5pPath()
    {
        // TODO: Implement getUploadedH5pPath() method.
    }

    public function loadAddons()
    {
        $addons = [];
        $sql = "
                SELECT l1.machine_name, l1.major_version, l1.minor_version, l1.patch_version,
                      l1.iid, l1.preloaded_js, l1.preloaded_css
                FROM plugin_h5p_import_library AS l1
                LEFT JOIN plugin_h5p_import_library AS l2
                ON l1.machine_name = l2.machine_name AND
                    (l1.major_version < l2.major_version OR
                    (l1.major_version = l2.major_version AND
                    l1.minor_version < l2.minor_version))
                WHERE l2.machine_name IS null
        ";

        $result = \Database::query($sql);
        while ($row = \Database::fetch_array($result)) {
            $addons[] = \H5PCore::snakeToCamel($row);
        }

        return $addons;
    }

    public function getLibraryConfig($libraries = null)
    {
        // TODO: Implement getLibraryConfig() method.
    }

    public function loadLibraries()
    {
        // TODO: Implement loadLibraries() method.
    }

    public function getAdminUrl()
    {
        // TODO: Implement getAdminUrl() method.
    }

    public function getLibraryId($machineName, $majorVersion = null, $minorVersion = null)
    {
        // TODO: Implement getLibraryId() method.
    }

    public function getWhitelist($isLibrary, $defaultContentWhitelist, $defaultLibraryWhitelist)
    {
        // TODO: Implement getWhitelist() method.
    }

    public function isPatchedLibrary($library)
    {
        // TODO: Implement isPatchedLibrary() method.
    }

    public function isInDevMode()
    {
        // TODO: Implement isInDevMode() method.
    }

    public function mayUpdateLibraries()
    {
        // TODO: Implement mayUpdateLibraries() method.
    }

    public function saveLibraryData(&$libraryData, $new = true)
    {
        // TODO: Implement saveLibraryData() method.
    }

    public function insertContent($content, $contentMainId = null)
    {
        // TODO: Implement insertContent() method.
    }

    public function updateContent($content, $contentMainId = null)
    {
        // TODO: Implement updateContent() method.
    }

    public function resetContentUserData($contentId)
    {
        // TODO: Implement resetContentUserData() method.
    }

    public function saveLibraryDependencies($libraryId, $dependencies, $dependency_type)
    {
        // TODO: Implement saveLibraryDependencies() method.
    }

    public function copyLibraryUsage($contentId, $copyFromId, $contentMainId = null)
    {
        // TODO: Implement copyLibraryUsage() method.
    }

    public function deleteContentData($contentId)
    {
        // TODO: Implement deleteContentData() method.
    }

    public function deleteLibraryUsage($contentId)
    {
        // TODO: Implement deleteLibraryUsage() method.
    }

    public function saveLibraryUsage($contentId, $librariesInUse)
    {
        // TODO: Implement saveLibraryUsage() method.
    }

    public function getLibraryUsage($libraryId, $skipContent = false)
    {
        // TODO: Implement getLibraryUsage() method.
    }

    public function loadLibrary($machineName, $majorVersion, $minorVersion)
    {
        if ($this->h5pImportLibraries) {
            $foundLibrary = $this->h5pImportLibraries->filter(
                function (H5pImportLibrary $library) use ($machineName, $majorVersion, $minorVersion) {
                    return $library->getLibraryByMachineNameAndVersions($machineName, $majorVersion, $minorVersion);
                }
            )->first();
            if ($foundLibrary) {
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
                ];
            }
        }

        return false;
    }

    public function loadLibrarySemantics($machineName, $majorVersion, $minorVersion)
    {
        // TODO: Implement loadLibrarySemantics() method.
    }

    public function alterLibrarySemantics(&$semantics, $machineName, $majorVersion, $minorVersion)
    {
        // TODO: Implement alterLibrarySemantics() method.
    }

    public function deleteLibraryDependencies($libraryId)
    {
        // TODO: Implement deleteLibraryDependencies() method.
    }

    public function lockDependencyStorage()
    {
        // TODO: Implement lockDependencyStorage() method.
    }

    public function unlockDependencyStorage()
    {
        // TODO: Implement unlockDependencyStorage() method.
    }

    public function deleteLibrary($library)
    {
        // TODO: Implement deleteLibrary() method.
    }

    public function loadContent($id): array
    {
        $contentJson = H5pPackageTools::getJson($this->h5pImport->getPath().'/content.json');
        $h5pJson = H5pPackageTools::getJson($this->h5pImport->getPath().'/h5p.json');

        if ($contentJson && $h5pJson) {
            $params = json_encode($contentJson);
            $embedType = implode(',', $h5pJson->embedTypes);
            $title = $this->h5pImport->getName();
            $language = $h5pJson->language;
            $libraryId = $this->h5pImport->getMainLibrary()->getIid();
            $libraryName = $this->h5pImport->getMainLibrary()->getMachineName();
            $libraryMajorVersion = $this->h5pImport->getMainLibrary()->getMajorVersion();
            $libraryMinorVersion = $this->h5pImport->getMainLibrary()->getMinorVersion();
            $libraryEmbedTypes = $this->h5pImport->getMainLibrary()->getEmbedTypesFormatted();

            // Create the associative array with the loaded content information. Use the unique folder name as id.
            return [
                'contentId' => basename($this->h5pImport->getPath()),
                'params' => $params,
                'embedType' => $embedType,
                'title' => $title,
                'language' => $language,
                'libraryId' => $libraryId,
                'libraryName' => $libraryName,
                'libraryMajorVersion' => $libraryMajorVersion,
                'libraryMinorVersion' => $libraryMinorVersion,
                'libraryEmbedTypes' => $libraryEmbedTypes,
                'libraryFullscreen' => 0,
            ];
        }

        return [];
    }

    public function loadContentDependencies($id, $type = null): array
    {
        $h5pImportLibraries = $this->h5pImportLibraries;
        $dependencies = [];

        /** @var H5pImportLibrary|null $library */
        foreach ($h5pImportLibraries as $library) {
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
        return api_get_course_plugin_setting('h5pimport', $name);
    }

    public function setOption($name, $value)
    {
        // TODO: Implement setOption() method.
    }

    public function updateContentFields($id, $fields)
    {
        // TODO: Implement updateContentFields() method.
    }

    public function clearFilteredParameters($library_ids)
    {
        // TODO: Implement clearFilteredParameters() method.
    }

    public function getNumNotFiltered()
    {
        // TODO: Implement getNumNotFiltered() method.
    }

    public function getNumContent($libraryId, $skip = null)
    {
        // TODO: Implement getNumContent() method.
    }

    public function isContentSlugAvailable($slug)
    {
        return true;
    }

    public function getLibraryStats($type)
    {
        // TODO: Implement getLibraryStats() method.
    }

    public function getNumAuthors()
    {
        // TODO: Implement getNumAuthors() method.
    }

    public function saveCachedAssets($key, $libraries)
    {
        // TODO: Implement saveCachedAssets() method.
    }

    public function deleteCachedAssets($library_id)
    {
        // TODO: Implement deleteCachedAssets() method.
    }

    public function getLibraryContentCount()
    {
        // TODO: Implement getLibraryContentCount() method.
    }

    public function afterExportCreated($content, $filename)
    {
        // TODO: Implement afterExportCreated() method.
    }

    public function hasPermission($permission, $id = null)
    {
        // TODO: Implement hasPermission() method.
    }

    public function replaceContentTypeCache($contentTypeCache)
    {
        // TODO: Implement replaceContentTypeCache() method.
    }

    public function libraryHasUpgrade($library)
    {
        // TODO: Implement libraryHasUpgrade() method.
    }
}
