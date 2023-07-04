<?php

namespace Chamilo\PluginBundle\H5pImport\H5pImporter;

use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImportLibrary;
use Database;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use H5PCore;
use H5PFrameworkInterface;
use Plugin;

class H5pImplementation implements H5PFrameworkInterface
{

    private H5pImport $h5pImport;
    private Collection $h5pImportLibraries;

    public function __construct(H5pImport $h5pImport)
    {
        $this->h5pImport = $h5pImport;
        $this->h5pImportLibraries = $h5pImport->getLibraries();
    }

    /**
     * @inheritDoc
     */
    public function getPlatformInfo()
    {
        // TODO: Implement getPlatformInfo() method.
    }

    /**
     * @inheritDoc
     */
    public function fetchExternalData($url, $data = NULL, $blocking = TRUE, $stream = NULL)
    {
        // TODO: Implement fetchExternalData() method.
    }

    /**
     * @inheritDoc
     */
    public function setLibraryTutorialUrl($machineName, $tutorialUrl)
    {
        // TODO: Implement setLibraryTutorialUrl() method.
    }

    /**
     * @inheritDoc
     */
    public function setErrorMessage($message, $code = NULL)
    {
        // TODO: Implement setErrorMessage() method.
    }

    /**
     * @inheritDoc
     */
    public function setInfoMessage($message)
    {
        // TODO: Implement setInfoMessage() method.
    }

    /**
     * @inheritDoc
     */
    public function getMessages($type)
    {
        // TODO: Implement getMessages() method.
    }

    /**
     * @inheritDoc
     */
    public function t($message, $replacements = array())
    {
       return get_lang($message);
    }

    /**
     * @inheritDoc
     */
    public function getLibraryFileUrl($libraryFolderName, $fileName)
    {
        // TODO: Implement getLibraryFileUrl() method.
    }

    /**
     * @inheritDoc
     */
    public function getUploadedH5pFolderPath()
    {
        // TODO: Implement getUploadedH5pFolderPath() method.
    }

    /**
     * @inheritDoc
     */
    public function getUploadedH5pPath()
    {
        // TODO: Implement getUploadedH5pPath() method.
    }

    /**
     * @inheritDoc
     */
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
                WHERE l2.machine_name IS NULL
        ";

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $addons[] = H5PCore::snakeToCamel($row);
        }

        return $addons;
    }

    /**
     * @inheritDoc
     */
    public function getLibraryConfig($libraries = NULL)
    {
        // TODO: Implement getLibraryConfig() method.
    }

    /**
     * @inheritDoc
     */
    public function loadLibraries()
    {
        // TODO: Implement loadLibraries() method.
    }

    /**
     * @inheritDoc
     */
    public function getAdminUrl()
    {
        // TODO: Implement getAdminUrl() method.
    }

    /**
     * @inheritDoc
     */
    public function getLibraryId($machineName, $majorVersion = NULL, $minorVersion = NULL)
    {
        // TODO: Implement getLibraryId() method.
    }

    /**
     * @inheritDoc
     */
    public function getWhitelist($isLibrary, $defaultContentWhitelist, $defaultLibraryWhitelist)
    {
        // TODO: Implement getWhitelist() method.
    }

    /**
     * @inheritDoc
     */
    public function isPatchedLibrary($library)
    {
        // TODO: Implement isPatchedLibrary() method.
    }

    /**
     * @inheritDoc
     */
    public function isInDevMode()
    {
        // TODO: Implement isInDevMode() method.
    }

    /**
     * @inheritDoc
     */
    public function mayUpdateLibraries()
    {
        // TODO: Implement mayUpdateLibraries() method.
    }

    /**
     * @inheritDoc
     */
    public function saveLibraryData(&$libraryData, $new = TRUE)
    {
        // TODO: Implement saveLibraryData() method.
    }

    /**
     * @inheritDoc
     */
    public function insertContent($content, $contentMainId = NULL)
    {
        // TODO: Implement insertContent() method.
    }

    /**
     * @inheritDoc
     */
    public function updateContent($content, $contentMainId = NULL)
    {
        // TODO: Implement updateContent() method.
    }

    /**
     * @inheritDoc
     */
    public function resetContentUserData($contentId)
    {
        // TODO: Implement resetContentUserData() method.
    }

    /**
     * @inheritDoc
     */
    public function saveLibraryDependencies($libraryId, $dependencies, $dependency_type)
    {
        // TODO: Implement saveLibraryDependencies() method.
    }

    /**
     * @inheritDoc
     */
    public function copyLibraryUsage($contentId, $copyFromId, $contentMainId = NULL)
    {
        // TODO: Implement copyLibraryUsage() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteContentData($contentId)
    {
        // TODO: Implement deleteContentData() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteLibraryUsage($contentId)
    {
        // TODO: Implement deleteLibraryUsage() method.
    }

    /**
     * @inheritDoc
     */
    public function saveLibraryUsage($contentId, $librariesInUse)
    {
        // TODO: Implement saveLibraryUsage() method.
    }

    /**
     * @inheritDoc
     */
    public function getLibraryUsage($libraryId, $skipContent = FALSE)
    {
        // TODO: Implement getLibraryUsage() method.
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function loadLibrarySemantics($machineName, $majorVersion, $minorVersion)
    {
        // TODO: Implement loadLibrarySemantics() method.
    }

    /**
     * @inheritDoc
     */
    public function alterLibrarySemantics(&$semantics, $machineName, $majorVersion, $minorVersion)
    {
        // TODO: Implement alterLibrarySemantics() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteLibraryDependencies($libraryId)
    {
        // TODO: Implement deleteLibraryDependencies() method.
    }

    /**
     * @inheritDoc
     */
    public function lockDependencyStorage()
    {
        // TODO: Implement lockDependencyStorage() method.
    }

    /**
     * @inheritDoc
     */
    public function unlockDependencyStorage()
    {
        // TODO: Implement unlockDependencyStorage() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteLibrary($library)
    {
        // TODO: Implement deleteLibrary() method.
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function getOption($name, $default = NULL)
    {
        return api_get_course_plugin_setting('h5pimport', $name);
    }

    /**
     * @inheritDoc
     */
    public function setOption($name, $value)
    {
        // TODO: Implement setOption() method.
    }

    /**
     * @inheritDoc
     */
    public function updateContentFields($id, $fields)
    {
        // TODO: Implement updateContentFields() method.
    }

    /**
     * @inheritDoc
     */
    public function clearFilteredParameters($library_ids)
    {
        // TODO: Implement clearFilteredParameters() method.
    }

    /**
     * @inheritDoc
     */
    public function getNumNotFiltered()
    {
        // TODO: Implement getNumNotFiltered() method.
    }

    /**
     * @inheritDoc
     */
    public function getNumContent($libraryId, $skip = NULL)
    {
        // TODO: Implement getNumContent() method.
    }

    /**
     * @inheritDoc
     */
    public function isContentSlugAvailable($slug)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getLibraryStats($type)
    {
        // TODO: Implement getLibraryStats() method.
    }

    /**
     * @inheritDoc
     */
    public function getNumAuthors()
    {
        // TODO: Implement getNumAuthors() method.
    }

    /**
     * @inheritDoc
     */
    public function saveCachedAssets($key, $libraries)
    {
        // TODO: Implement saveCachedAssets() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteCachedAssets($library_id)
    {
        // TODO: Implement deleteCachedAssets() method.
    }

    /**
     * @inheritDoc
     */
    public function getLibraryContentCount()
    {
        // TODO: Implement getLibraryContentCount() method.
    }

    /**
     * @inheritDoc
     */
    public function afterExportCreated($content, $filename)
    {
        // TODO: Implement afterExportCreated() method.
    }

    /**
     * @inheritDoc
     */
    public function hasPermission($permission, $id = NULL)
    {
        // TODO: Implement hasPermission() method.
    }

    /**
     * @inheritDoc
     */
    public function replaceContentTypeCache($contentTypeCache)
    {
        // TODO: Implement replaceContentTypeCache() method.
    }

    /**
     * @inheritDoc
     */
    public function libraryHasUpgrade($library)
    {
        // TODO: Implement libraryHasUpgrade() method.
    }
}
