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

    public function resetHubOrganizationData()
    {
        $this->contentHubMetadata = [];
        $this->contentHubChecked = [];

        $this->setOption('site_uuid', null);
        $this->setOption('hub_secret', null);

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
        return H5pPackageTools::buildPackageAssetUrl(
            H5pPackageTools::buildCourseRelativePrefix($this->h5pImport->getCourse())
            .'/libraries/'.$libraryFolderName.'/'.$fileName
        );
    }

    public function getUploadedH5pFolderPath()
    {
        $path = H5pPackageTools::getCourseStoragePath($this->h5pImport->getCourse()).'/tmp';

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
        $rows = $this->findAllLibraryRows();
        $libraries = [];

        foreach ($rows as $row) {
            $library = [
                'libraryId' => (int) $row['iid'],
                'title' => (string) $row['title'],
                'machineName' => (string) $row['machine_name'],
                'majorVersion' => (int) $row['major_version'],
                'minorVersion' => (int) $row['minor_version'],
                'patchVersion' => (int) $row['patch_version'],
                'runnable' => (int) $row['runnable'],
                'embedTypes' => $this->normalizeEmbedTypes($row['embed_types'] ?? ''),
                'fullscreen' => 0,
                'hasIcon' => false,
                'addTo' => $this->normalizeAddTo($row['add_to'] ?? null),
            ];

            $library['preloadedJs'] = $this->normalizeAssetList(
                $this->csvToAssetPaths($row['preloaded_js'] ?? ''),
                $library
            );

            $library['preloadedCss'] = $this->normalizeAssetList(
                $this->csvToAssetPaths($row['preloaded_css'] ?? ''),
                $library
            );

            $libraries[] = $library;
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

    public function loadLibrary($name, $majorVersion, $minorVersion)
    {
        $row = $this->findLibraryRow($name, (int) $majorVersion, (int) $minorVersion);

        if (!$row) {
            return false;
        }

        $library = [
            'libraryId' => (int) $row['iid'],
            'title' => (string) $row['title'],
            'machineName' => (string) $row['machine_name'],
            'majorVersion' => (int) $row['major_version'],
            'minorVersion' => (int) $row['minor_version'],
            'patchVersion' => (int) $row['patch_version'],
            'runnable' => (int) $row['runnable'],
            'embedTypes' => $this->normalizeEmbedTypes($row['embed_types'] ?? ''),
            'preloadedDependencies' => $this->getLibraryDependencies((int) $row['iid']),
            'fullscreen' => 0,
            'hasIcon' => false,
            'addTo' => $this->normalizeAddTo($row['add_to'] ?? null),
        ];

        $library['preloadedJs'] = $this->normalizeAssetList(
            $this->csvToAssetPaths($row['preloaded_js'] ?? ''),
            $library
        );

        $library['preloadedCss'] = $this->normalizeAssetList(
            $this->csvToAssetPaths($row['preloaded_css'] ?? ''),
            $library
        );

        return $library;
    }

    public function loadLibrarySemantics($name, $majorVersion, $minorVersion)
    {
        $row = $this->findLibraryRow($name, (int) $majorVersion, (int) $minorVersion);

        if (!$row) {
            return '';
        }

        $libraryPath = (string) ($row['library_path'] ?? '');

        if ('' === $libraryPath) {
            return '';
        }

        $semanticsFile = rtrim($libraryPath, '/').'/semantics.json';

        if (!is_file($semanticsFile) || !is_readable($semanticsFile)) {
            return '';
        }

        $contents = file_get_contents($semanticsFile);

        return false === $contents ? '' : $contents;
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
        $encodedParams = json_encode($contentJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return [
            'id' => $this->h5pImport->getIid(),
            'contentId' => $this->h5pImport->getIid(),
            'mainId' => $this->h5pImport->getIid(),
            'slug' => (string) $this->h5pImport->getIid(),
            'params' => false === $encodedParams ? '{}' : $encodedParams,
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
                'title' => $mainLibrary->getTitle(),
                'machineName' => $mainLibrary->getMachineName(),
                'majorVersion' => $mainLibrary->getMajorVersion(),
                'minorVersion' => $mainLibrary->getMinorVersion(),
                'patchVersion' => $mainLibrary->getPatchVersion(),
                'runnable' => $mainLibrary->getRunnable(),
                'embedTypes' => $embedTypes,
                'fullscreen' => 0,
                'hasIcon' => false,
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
                'preloadedJs' => implode(',', $this->extractAssetPaths($library->getPreloadedJs() ?? [])),
                'preloadedCss' => implode(',', $this->extractAssetPaths($library->getPreloadedCss() ?? [])),
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

    private function normalizeEmbedTypes($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, static fn ($item) => is_string($item) && '' !== trim($item)));
        }

        if (!is_string($value) || '' === trim($value)) {
            return ['div'];
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            $items = array_values(array_filter($decoded, static fn ($item) => is_string($item) && '' !== trim($item)));

            return [] === $items ? ['div'] : $items;
        }

        $items = array_map('trim', explode(',', $value));
        $items = array_values(array_filter($items, static fn (string $item) => '' !== $item));

        return [] === $items ? ['div'] : $items;
    }

    private function mapLibraryEntityToRow(H5pImportLibrary $library): array
    {
        return [
            'iid' => $library->getIid(),
            'title' => $library->getTitle(),
            'machine_name' => $library->getMachineName(),
            'major_version' => $library->getMajorVersion(),
            'minor_version' => $library->getMinorVersion(),
            'patch_version' => $library->getPatchVersion(),
            'runnable' => $library->getRunnable(),
            'embed_types' => $library->getEmbedTypes(),
            'preloaded_js' => implode(',', $this->extractAssetPaths($library->getPreloadedJs() ?? [])),
            'preloaded_css' => implode(',', $this->extractAssetPaths($library->getPreloadedCss() ?? [])),
            'add_to' => null,
            'semantics' => '',
            'library_path' => $library->getLibraryPath(),
        ];
    }

    private function extractAssetPaths(?array $assets): array
    {
        if (empty($assets)) {
            return [];
        }

        $paths = [];

        foreach ($assets as $asset) {
            if (is_string($asset)) {
                $asset = trim($asset);

                if ('' !== $asset) {
                    $paths[] = $asset;
                }

                continue;
            }

            if (is_array($asset)) {
                $path = trim((string) ($asset['path'] ?? $asset['src'] ?? ''));

                if ('' !== $path) {
                    $paths[] = $path;
                }

                continue;
            }

            if (is_object($asset)) {
                $path = trim((string) ($asset->path ?? $asset->src ?? ''));

                if ('' !== $path) {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    private function findAllLibraryRows(): array
    {
        $rows = [];

        /** @var H5pImportLibrary $library */
        foreach ($this->h5pImportLibraries as $library) {
            $rows[] = $this->mapLibraryEntityToRow($library);
        }

        return $rows;
    }

    private function findLibraryRow(string $name, int $majorVersion, int $minorVersion): ?array
    {
        /** @var H5pImportLibrary $library */
        foreach ($this->h5pImportLibraries as $library) {
            if (
                $library->getMachineName() === $name
                && $library->getMajorVersion() === $majorVersion
                && $library->getMinorVersion() === $minorVersion
            ) {
                return $this->mapLibraryEntityToRow($library);
            }
        }

        return null;
    }

    private function getLibraryDependencies(int $libraryId): array
    {
        return [];
    }

    private function normalizeAssetList(?array $assets, array $library): array
    {
        if (empty($assets)) {
            return [];
        }

        $normalized = [];
        $defaultVersion = $this->buildAssetVersion($library);
        $defaultAddTo = $this->normalizeAddTo($library['addTo'] ?? null);

        foreach ($assets as $asset) {
            $path = '';
            $version = $defaultVersion;
            $addTo = $defaultAddTo;

            if (is_string($asset)) {
                $path = trim($asset);
            } elseif (is_array($asset)) {
                $path = trim((string) ($asset['path'] ?? $asset['src'] ?? ''));
                $version = (string) ($asset['version'] ?? $defaultVersion);
                $addTo = $this->normalizeAddTo($asset['addTo'] ?? $defaultAddTo);
            }

            if ('' === $path) {
                continue;
            }

            $normalized[] = [
                'path' => $path,
                'version' => $version,
                'addTo' => $addTo,
            ];
        }

        return $normalized;
    }

    private function normalizeAddTo(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, static fn ($item) => is_string($item) && '' !== trim($item)));
        }

        if (is_string($value) && '' !== trim($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_filter($decoded, static fn ($item) => is_string($item) && '' !== trim($item)));
            }
        }

        return [];
    }

    private function buildAssetVersion(array $library): string
    {
        $major = (string) ($library['majorVersion'] ?? $library['major_version'] ?? '0');
        $minor = (string) ($library['minorVersion'] ?? $library['minor_version'] ?? '0');
        $patch = (string) ($library['patchVersion'] ?? $library['patch_version'] ?? '0');

        return $major.'.'.$minor.'.'.$patch;
    }

    private function csvToAssetPaths(?string $csv): array
    {
        if (null === $csv || '' === trim($csv)) {
            return [];
        }

        $items = array_map('trim', explode(',', $csv));

        return array_values(array_filter($items, static fn (string $item) => '' !== $item));
    }
}
