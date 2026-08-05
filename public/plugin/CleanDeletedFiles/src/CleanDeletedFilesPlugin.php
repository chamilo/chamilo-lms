<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Doctrine\DBAL\Connection;

/**
 * Clean deleted files plugin.
 *
 * Chamilo 2 stores files through resource_file and asset metadata. This plugin
 * only removes physical files that are already orphaned from those metadata
 * tables. It does not scan obsolete Chamilo 1.x storage roots.
 */
class CleanDeletedFilesPlugin extends Plugin
{
    private const MAX_SCAN_FILES = 5000;

    private const MAX_SCAN_SECONDS = 4;

    private const TYPE_RESOURCE_ORPHAN = 'resource_orphan';
    private const TYPE_ASSET_ORPHAN = 'asset_orphan';

    /**
     * @var array<string, string>|null
     */
    private ?array $referencedResourcePaths = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $referencedAssetPaths = null;

    private bool $lastScanWasLimited = false;

    protected function __construct()
    {
        $version = '1.3';
        $author = 'José Angel Ruiz (NOSOLORED), Chamilo';

        parent::__construct($version, $author, ['enabled' => 'boolean']);

        $this->isAdminPlugin = true;
    }

    public static function create(): self
    {
        static $result = null;

        return $result instanceof self ? $result : $result = new self();
    }

    /**
     * @return array<int, array{relative_path: string, root: string, label: string, storage_type: string, candidate_type: string, size_bytes: int, size: string, can_delete: bool, reason: string}>
     */
    public function findCleanableFileCandidates(string $pathFilter = ''): array
    {
        $this->lastScanWasLimited = false;
        $result = [];
        $pathFilter = $this->normalizePathFilter($pathFilter);

        foreach ($this->getScanRoots() as $root) {
            $result = array_merge(
                $result,
                $this->collectCandidates($root['path'], $root['label'], $root['type'], $pathFilter)
            );

            if ($this->lastScanWasLimited) {
                break;
            }
        }

        usort($result, static fn (array $a, array $b): int => strcmp($a['relative_path'], $b['relative_path']));

        return $result;
    }

    public function wasLastScanLimited(): bool
    {
        return $this->lastScanWasLimited;
    }

    /**
     * Backward-compatible alias for older admin.php versions.
     *
     * @return array<int, array{relative_path: string, root: string, label: string, storage_type: string, candidate_type: string, size_bytes: int, size: string, can_delete: bool, reason: string}>
     */
    public function findDeletedFileCandidates(string $pathFilter = ''): array
    {
        return $this->findCleanableFileCandidates($pathFilter);
    }

    /**
     * @param array<int, mixed> $list
     *
     * @return array{success: bool, message: string, deleted: int, skipped: int, errors: array<int, string>, deleted_paths: array<int, string>}
     */
    public function deleteRelativePathList(array $list): array
    {
        $deleted = 0;
        $skipped = 0;
        $errors = [];
        $deletedPaths = [];

        foreach ($list as $relativePath) {
            if (!is_string($relativePath) || '' === trim($relativePath)) {
                ++$skipped;

                continue;
            }

            $deleteResult = $this->deleteRelativePath($relativePath);
            if ($deleteResult['success']) {
                ++$deleted;
                if (isset($deleteResult['deleted_path']) && is_string($deleteResult['deleted_path'])) {
                    $deletedPaths[] = $deleteResult['deleted_path'];
                }

                continue;
            }

            ++$skipped;
            $errors[] = $relativePath.': '.$deleteResult['message'];
        }

        return [
            'success' => $deleted > 0,
            'message' => sprintf(
                '%s: %d. %s: %d.',
                $this->get_lang('DeletedFilesCount'),
                $deleted,
                $this->get_lang('SkippedFilesCount'),
                $skipped
            ),
            'deleted' => $deleted,
            'skipped' => $skipped,
            'errors' => $errors,
            'deleted_paths' => $deletedPaths,
        ];
    }

    /**
     * @return array{success: bool, message: string, deleted_path?: string}
     */
    public function deleteRelativePath(string $relativePath): array
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ('' === $relativePath) {
            return [
                'success' => false,
                'message' => $this->get_lang('ErrorEmptyPath'),
            ];
        }

        $realPath = $this->getRealPathForAllowedFile($relativePath);
        if (null === $realPath) {
            return [
                'success' => false,
                'message' => $this->get_lang('ErrorInvalidPath'),
            ];
        }

        $candidate = $this->buildCandidateForRelativePath($relativePath, $realPath);
        if (null === $candidate || !$candidate['can_delete']) {
            return [
                'success' => false,
                'message' => $this->get_lang('ErrorNotCleanablePath'),
            ];
        }

        if (!is_writable(dirname($realPath))) {
            return [
                'success' => false,
                'message' => $this->get_lang('ErrorDeleteFile').' Directory is not writable by the web server: '.dirname($relativePath),
            ];
        }

        if (!@unlink($realPath)) {
            $lastError = error_get_last();
            $detail = is_array($lastError) && isset($lastError['message']) ? ' '.$lastError['message'] : '';

            return [
                'success' => false,
                'message' => $this->get_lang('ErrorDeleteFile').$detail,
            ];
        }

        clearstatcache(true, $realPath);
        if (is_file($realPath)) {
            return [
                'success' => false,
                'message' => $this->get_lang('ErrorDeleteFile').' The file still exists after unlink().',
            ];
        }

        return [
            'success' => true,
            'message' => $this->get_lang('DeletedSuccess'),
            'deleted_path' => $relativePath,
        ];
    }

    /**
     * @return array<int, array{path: string, label: string, type: string, description: string}>
     */
    public function getScanRoots(): array
    {
        return [
            [
                'path' => 'var/upload/resource',
                'label' => $this->get_lang('ResourceFiles'),
                'type' => self::TYPE_RESOURCE_ORPHAN,
                'description' => $this->get_lang('ResourceStorageHelp'),
            ],
            [
                'path' => 'var/upload/assets',
                'label' => $this->get_lang('AssetFiles'),
                'type' => self::TYPE_ASSET_ORPHAN,
                'description' => $this->get_lang('AssetStorageHelp'),
            ],
        ];
    }

    public function getCandidateTypeLabel(string $candidateType): string
    {
        return match ($candidateType) {
            self::TYPE_RESOURCE_ORPHAN => $this->get_lang('OrphanResourceFile'),
            self::TYPE_ASSET_ORPHAN => $this->get_lang('OrphanAssetFile'),
            default => $candidateType,
        };
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' Mb';
        }

        if ($bytes <= 0) {
            return '0 Kb';
        }

        return round($bytes / 1024, 1).' Kb';
    }

    /**
     * @return array<string, int>
     */
    public function getDatabaseReferenceSummary(): array
    {
        return [
            'resource_file' => $this->countTableRows('resource_file'),
            'asset' => $this->countTableRows('asset'),
        ];
    }

    /**
     * @return array<int, array{relative_path: string, root: string, label: string, storage_type: string, candidate_type: string, size_bytes: int, size: string, can_delete: bool, reason: string}>
     */
    private function collectCandidates(string $relativeRoot, string $label, string $candidateType, string $pathFilter = ''): array
    {
        $projectRoot = $this->getProjectRoot();
        $rootPath = $projectRoot.'/'.$relativeRoot;

        if (!is_dir($rootPath)) {
            return [];
        }

        $rootRealPath = realpath($rootPath);
        if (false === $rootRealPath) {
            return [];
        }

        $result = [];
        $scannedFiles = 0;
        $startedAt = microtime(true);

        foreach ($this->buildScanTargets($rootRealPath, $relativeRoot, $pathFilter) as $targetPath) {
            if (is_file($targetPath)) {
                $this->appendCandidateFromFile(
                    new SplFileInfo($targetPath),
                    $projectRoot,
                    $relativeRoot,
                    $label,
                    $candidateType,
                    $pathFilter,
                    $result,
                    $scannedFiles,
                    $startedAt
                );

                if ($this->lastScanWasLimited) {
                    break;
                }

                continue;
            }

            if (!is_dir($targetPath)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($targetPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                    continue;
                }

                $this->appendCandidateFromFile(
                    $fileInfo,
                    $projectRoot,
                    $relativeRoot,
                    $label,
                    $candidateType,
                    $pathFilter,
                    $result,
                    $scannedFiles,
                    $startedAt
                );

                if ($this->lastScanWasLimited) {
                    break 2;
                }
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function buildScanTargets(string $rootRealPath, string $relativeRoot, string $pathFilter): array
    {
        $pathFilter = $this->normalizePathFilter($pathFilter);
        if ('' === $pathFilter) {
            return [$rootRealPath];
        }

        $projectRoot = $this->getProjectRoot();
        $candidates = [];

        $filterWithoutStorageRoot = $pathFilter;
        if (str_starts_with($filterWithoutStorageRoot, $relativeRoot.'/')) {
            $filterWithoutStorageRoot = substr($filterWithoutStorageRoot, strlen($relativeRoot) + 1);
        }

        foreach ([$pathFilter, $filterWithoutStorageRoot] as $candidateFilter) {
            if ('' === $candidateFilter) {
                continue;
            }

            $absoluteCandidate = $projectRoot.'/'.$candidateFilter;
            $this->addScanTargetIfAllowed($candidates, $absoluteCandidate, $rootRealPath);

            $absoluteCandidate = $rootRealPath.'/'.$candidateFilter;
            $this->addScanTargetIfAllowed($candidates, $absoluteCandidate, $rootRealPath);
        }

        if ([] !== $candidates) {
            return array_values(array_unique($candidates));
        }

        return [$rootRealPath];
    }

    /**
     * @param array<int, string> $targets
     */
    private function addScanTargetIfAllowed(array &$targets, string $path, string $rootRealPath): void
    {
        $realPath = realpath($path);
        if (false === $realPath) {
            return;
        }

        if ($realPath !== $rootRealPath && !str_starts_with($realPath, $rootRealPath.DIRECTORY_SEPARATOR)) {
            return;
        }

        if (!is_dir($realPath) && !is_file($realPath)) {
            return;
        }

        $targets[] = $realPath;
    }

    /**
     * @param array<int, array{relative_path: string, root: string, label: string, storage_type: string, candidate_type: string, size_bytes: int, size: string, can_delete: bool, reason: string}> $result
     */
    private function appendCandidateFromFile(
        SplFileInfo $fileInfo,
        string $projectRoot,
        string $relativeRoot,
        string $label,
        string $candidateType,
        string $pathFilter,
        array &$result,
        int &$scannedFiles,
        float $startedAt
    ): void {
        $absolutePath = $fileInfo->getRealPath();
        if (false === $absolutePath) {
            return;
        }

        $relativePath = $this->normalizeRelativePath(substr($absolutePath, strlen($projectRoot) + 1));
        if ('' !== $pathFilter && !str_contains(strtolower($relativePath), strtolower($pathFilter))) {
            return;
        }

        ++$scannedFiles;
        if ($scannedFiles > self::MAX_SCAN_FILES || microtime(true) - $startedAt > self::MAX_SCAN_SECONDS) {
            $this->lastScanWasLimited = true;

            return;
        }

        $candidate = $this->buildCandidateForRelativePath($relativePath, $absolutePath);
        if (null === $candidate || $candidate['candidate_type'] !== $candidateType) {
            return;
        }

        $candidate['root'] = $relativeRoot;
        $candidate['label'] = $label;
        $result[] = $candidate;
    }

    /**
     * @return array{relative_path: string, root: string, label: string, storage_type: string, candidate_type: string, size_bytes: int, size: string, can_delete: bool, reason: string}|null
     */
    private function buildCandidateForRelativePath(string $relativePath, string $absolutePath): ?array
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        if ('' === $relativePath || !is_file($absolutePath)) {
            return null;
        }

        $candidateType = $this->getCandidateTypeForRelativePath($relativePath);
        if (null === $candidateType) {
            return null;
        }

        $reason = '';
        if (self::TYPE_RESOURCE_ORPHAN === $candidateType) {
            if ($this->isReferencedResourcePath($relativePath)) {
                return null;
            }

            $reason = $this->get_lang('ReasonOrphanResource');
        } elseif (self::TYPE_ASSET_ORPHAN === $candidateType) {
            if ($this->isReferencedAssetPath($relativePath)) {
                return null;
            }

            $reason = $this->get_lang('ReasonOrphanAsset');
        }

        return [
            'relative_path' => $relativePath,
            'root' => '',
            'label' => '',
            'storage_type' => $this->getCandidateTypeLabel($candidateType),
            'candidate_type' => $candidateType,
            'size_bytes' => (int) filesize($absolutePath),
            'size' => $this->formatBytes((int) filesize($absolutePath)),
            'can_delete' => true,
            'reason' => $reason,
        ];
    }

    private function getCandidateTypeForRelativePath(string $relativePath): ?string
    {
        if (str_starts_with($relativePath, 'var/upload/resource/')) {
            return self::TYPE_RESOURCE_ORPHAN;
        }

        if (str_starts_with($relativePath, 'var/upload/assets/')) {
            return self::TYPE_ASSET_ORPHAN;
        }

        return null;
    }

    private function getProjectRoot(): string
    {
        $candidates = [
            dirname(__DIR__, 4),
            rtrim(api_get_path(SYS_PATH), '/'),
            dirname(rtrim(api_get_path(SYS_PATH), '/')),
        ];

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if (false === $realPath) {
                continue;
            }

            if (is_dir($realPath.'/var/upload/resource') || is_dir($realPath.'/var/upload/assets')) {
                return rtrim($realPath, '/');
            }
        }

        return rtrim((string) realpath(dirname(__DIR__, 4)), '/');
    }

    public function normalizePathFilter(string $pathFilter): string
    {
        $pathFilter = str_replace('\\', '/', trim($pathFilter));
        $pathFilter = ltrim($pathFilter, '/');

        if (str_contains($pathFilter, "\0") || str_contains($pathFilter, '..')) {
            return '';
        }

        return $pathFilter;
    }

    private function normalizeRelativePath(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        $relativePath = ltrim($relativePath, '/');

        if (str_contains($relativePath, "\0")) {
            return '';
        }

        $parts = [];
        foreach (explode('/', $relativePath) as $part) {
            if ('' === $part || '.' === $part) {
                continue;
            }

            if ('..' === $part) {
                return '';
            }

            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    private function getRealPathForAllowedFile(string $relativePath): ?string
    {
        $projectRoot = $this->getProjectRoot();
        $absolutePath = $projectRoot.'/'.$relativePath;
        $realPath = realpath($absolutePath);

        if (false === $realPath || !is_file($realPath)) {
            return null;
        }

        if (!$this->isAllowedRoot($realPath)) {
            return null;
        }

        return $realPath;
    }

    private function isAllowedRoot(string $realPath): bool
    {
        foreach ($this->getScanRoots() as $root) {
            $rootRealPath = realpath($this->getProjectRoot().'/'.$root['path']);
            if (false === $rootRealPath) {
                continue;
            }

            if ($realPath === $rootRealPath || str_starts_with($realPath, $rootRealPath.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }


    private function isReferencedResourcePath(string $relativePath): bool
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        $storagePath = $this->removePrefix($relativePath, 'var/upload/resource/');

        return isset($this->getReferencedResourcePaths()[$relativePath])
            || isset($this->getReferencedResourcePaths()[$storagePath]);
    }

    private function isReferencedAssetPath(string $relativePath): bool
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        $storagePath = $this->removePrefix($relativePath, 'var/upload/assets/');

        return isset($this->getReferencedAssetPaths()[$relativePath])
            || isset($this->getReferencedAssetPaths()[$storagePath]);
    }

    private function removePrefix(string $path, string $prefix): string
    {
        if (str_starts_with($path, $prefix)) {
            return substr($path, strlen($prefix));
        }

        return $path;
    }

    /**
     * @return array<string, string>
     */
    private function getReferencedResourcePaths(): array
    {
        if (null !== $this->referencedResourcePaths) {
            return $this->referencedResourcePaths;
        }

        $this->referencedResourcePaths = [];

        try {
            $connection = Database::getManager()->getConnection();
            if (!$connection instanceof Connection || !$this->tableExists($connection, 'resource_file')) {
                return $this->referencedResourcePaths;
            }

            $rows = $connection->fetchAllAssociative(
                'SELECT id, title FROM resource_file WHERE title IS NOT NULL AND title <> ""'
            );

            foreach ($rows as $row) {
                $title = $this->normalizeStorageName((string) ($row['title'] ?? ''));
                if ('' === $title) {
                    continue;
                }

                $reason = 'resource_file #'.(int) $row['id'];
                $this->addReferencedPath($this->referencedResourcePaths, $title, $reason);
                $this->addReferencedPath($this->referencedResourcePaths, 'var/upload/resource/'.$title, $reason);

                $subdirPath = $this->buildResourceSubdirectoryPath($title);
                if ('' !== $subdirPath) {
                    $this->addReferencedPath($this->referencedResourcePaths, $subdirPath.'/'.$title, $reason);
                    $this->addReferencedPath($this->referencedResourcePaths, 'var/upload/resource/'.$subdirPath.'/'.$title, $reason);
                }
            }
        } catch (Throwable $exception) {
            error_log('[CleanDeletedFiles] Failed to load resource_file paths: '.$exception->getMessage());
        }

        return $this->referencedResourcePaths;
    }

    /**
     * @return array<string, string>
     */
    private function getReferencedAssetPaths(): array
    {
        if (null !== $this->referencedAssetPaths) {
            return $this->referencedAssetPaths;
        }

        $this->referencedAssetPaths = [];

        try {
            $connection = Database::getManager()->getConnection();
            if (!$connection instanceof Connection || !$this->tableExists($connection, 'asset')) {
                return $this->referencedAssetPaths;
            }

            $rows = $connection->fetchAllAssociative(
                'SELECT id, title, category FROM asset WHERE title IS NOT NULL AND title <> ""'
            );

            foreach ($rows as $row) {
                $title = $this->normalizeStorageName((string) ($row['title'] ?? ''));
                if ('' === $title) {
                    continue;
                }

                $category = $this->normalizeStorageName((string) ($row['category'] ?? ''));
                $reason = 'asset #'.(string) ($row['id'] ?? '');

                $this->addReferencedPath($this->referencedAssetPaths, $title, $reason);
                $this->addReferencedPath($this->referencedAssetPaths, 'var/upload/assets/'.$title, $reason);

                foreach ($this->buildAssetStoragePaths($category, $title) as $assetPath) {
                    $this->addReferencedPath($this->referencedAssetPaths, $assetPath, $reason);
                    $this->addReferencedPath($this->referencedAssetPaths, 'var/upload/assets/'.$assetPath, $reason);
                }
            }
        } catch (Throwable $exception) {
            error_log('[CleanDeletedFiles] Failed to load asset paths: '.$exception->getMessage());
        }

        return $this->referencedAssetPaths;
    }

    /**
     * @param array<string, string> $paths
     */
    private function addReferencedPath(array &$paths, string $path, string $reason): void
    {
        $path = $this->normalizeRelativePath($path);
        if ('' !== $path) {
            $paths[$path] = $reason;
        }
    }

    private function normalizeStorageName(string $name): string
    {
        $name = str_replace('\\', '/', trim($name));
        $name = ltrim($name, '/');

        if (str_contains($name, "\0") || str_contains($name, '../')) {
            return '';
        }

        return $name;
    }

    /**
     * Vich mapping "resources" uses SubdirDirectoryNamer with chars_per_dir=1, dirs=3.
     */
    private function buildResourceSubdirectoryPath(string $fileName): string
    {
        $cleanName = ltrim($fileName, '/');
        if ('' === $cleanName) {
            return '';
        }

        $parts = [];
        for ($i = 0; $i < 3; ++$i) {
            if (!isset($cleanName[$i])) {
                break;
            }

            $parts[] = $cleanName[$i];
        }

        return implode('/', $parts);
    }

    /**
     * Mirrors Chamilo\CoreBundle\Component\VichUploader\AssetDirectoryNamer.
     *
     * @return array<int, string>
     */
    private function buildAssetStoragePaths(string $category, string $fileName): array
    {
        if ('' === $category || '' === $fileName) {
            return [$fileName];
        }

        if ('system_template' === $category) {
            return ['system_templates/'.$fileName];
        }

        if ('template' === $category) {
            return ['doc_templates/'.$fileName];
        }

        if ('ef' === $category) {
            return [$category.'/'.substr($fileName, 0, 2).'/'.$fileName];
        }

        return [$category.'/'.$fileName.'/'.$fileName];
    }

    private function countTableRows(string $tableName): int
    {
        try {
            $connection = Database::getManager()->getConnection();
            if (!$connection instanceof Connection || !$this->tableExists($connection, $tableName)) {
                return 0;
            }

            return (int) $connection->fetchOne('SELECT COUNT(*) FROM '.$tableName);
        } catch (Throwable $exception) {
            error_log('[CleanDeletedFiles] Failed to count table '.$tableName.': '.$exception->getMessage());

            return 0;
        }
    }


    private function tableExists(Connection $connection, string $tableName): bool
    {
        try {
            return $connection->createSchemaManager()->tablesExist([$tableName]);
        } catch (Throwable $exception) {
            error_log('[CleanDeletedFiles] Failed to check table '.$tableName.': '.$exception->getMessage());

            return false;
        }
    }
}
