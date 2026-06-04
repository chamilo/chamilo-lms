<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateApplyPlanResult;
use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

final readonly class UpdateApplyPlanner
{
    private const FILE_LIST_LIMIT = 200;
    private const SKIPPED_EXACT_PATHS = [
        '.env',
        '.env.local',
        'app/config/configuration.php',
    ];
    private const SKIPPED_PREFIXES = [
        '.git',
        'node_modules',
        'vendor',
        'var',
        'public/courses',
        'public/upload',
    ];

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {}

    public function buildPlan(string $stagingPath): UpdateApplyPlanResult
    {
        $checks = [];
        $warnings = [];
        $details = [
            'project_dir' => $this->projectDir,
            'requested_staging_path' => $stagingPath,
        ];

        try {
            $stagingPath = $this->resolveSafeStagingPath($stagingPath);
            $details['staging_path'] = $stagingPath;
            $this->addCheck($checks, 'staging_path', 'passed', 'Staging directory is inside the Chamilo update staging directory.', [
                'staging_path' => $stagingPath,
            ]);

            $metadata = $this->readStagingMetadata($stagingPath);
            $details['staging_metadata'] = $this->summarizeStagingMetadata($metadata);
            $this->addCheck($checks, 'staging_metadata', 'passed', 'Staging metadata was read successfully.');

            $applicationPath = $this->resolveApplicationPath($stagingPath, $metadata);
            $details['application_path'] = $applicationPath;
            $this->addCheck($checks, 'application_path', 'passed', 'Staged application path is valid.', [
                'application_path' => $applicationPath,
            ]);

            $lockPath = $this->projectDir.'/var/update/update.lock';
            $this->checkUpdateLock($lockPath, $checks);

            $backupPath = $this->buildBackupPath($metadata);
            $details['backup_path'] = $backupPath;
            $this->checkBackupReadiness($backupPath, $checks);
            $this->checkUpdateWorkingDirectories($stagingPath, $lockPath, $backupPath, $checks);

            $filePlan = $this->buildFilePlan($applicationPath);
            $details['file_plan'] = $filePlan;
            $this->addFilePlanChecks($filePlan, $checks, $warnings);

            $errors = $this->collectFailedCheckMessages($checks);

            if ([] !== $errors) {
                return UpdateApplyPlanResult::failure($errors, $checks, $warnings, $details);
            }

            $this->writeApplyPlanMetadata($stagingPath, $metadata, $applicationPath, $backupPath, $lockPath, $filePlan);
            $this->addCheck($checks, 'apply_plan_metadata', 'passed', 'Apply plan metadata was written to the staging directory.', [
                'metadata_file' => $stagingPath.'/APPLY-PLAN.json',
            ]);

            return UpdateApplyPlanResult::success($stagingPath, $applicationPath, $backupPath, $lockPath, $checks, $warnings, $details);
        } catch (RuntimeException $exception) {
            return UpdateApplyPlanResult::failure([$exception->getMessage()], $checks, $warnings, $details);
        }
    }

    private function resolveSafeStagingPath(string $stagingPath): string
    {
        $stagingPath = rtrim(trim($stagingPath), '/');

        if ('' === $stagingPath) {
            throw new RuntimeException('Staging path is required to build an update apply plan.');
        }

        $realStagingPath = realpath($stagingPath);

        if (false === $realStagingPath || !is_dir($realStagingPath)) {
            throw new RuntimeException('Staging directory does not exist: '.$stagingPath);
        }

        $stagingBasePath = realpath($this->projectDir.'/var/update/staging');

        if (false === $stagingBasePath) {
            throw new RuntimeException('Chamilo update staging base directory does not exist.');
        }

        if (!$this->isPathInside($realStagingPath, $stagingBasePath)) {
            throw new RuntimeException('Staging directory must be inside var/update/staging.');
        }

        if (!is_file($realStagingPath.'/STAGING-INFO.json')) {
            throw new RuntimeException('Staging directory is missing STAGING-INFO.json.');
        }

        return $realStagingPath;
    }

    /**
     * @return array<string, mixed>
     */
    private function readStagingMetadata(string $stagingPath): array
    {
        $metadataPath = $stagingPath.'/STAGING-INFO.json';
        $content = file_get_contents($metadataPath);

        if (false === $content) {
            throw new RuntimeException('Unable to read staging metadata: '.$metadataPath);
        }

        try {
            $metadata = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Staging metadata is not valid JSON: '.$exception->getMessage(), 0, $exception);
        }

        if (!\is_array($metadata)) {
            throw new RuntimeException('Staging metadata must be a JSON object.');
        }

        return $metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function resolveApplicationPath(string $stagingPath, array $metadata): string
    {
        $applicationPath = $metadata['application_path'] ?? null;

        if (!\is_string($applicationPath) || '' === trim($applicationPath)) {
            throw new RuntimeException('Staging metadata is missing the application_path field.');
        }

        $realApplicationPath = realpath($applicationPath);

        if (false === $realApplicationPath || !is_dir($realApplicationPath)) {
            throw new RuntimeException('Staged application path is not readable: '.$applicationPath);
        }

        if (!$this->isPathInside($realApplicationPath, $stagingPath)) {
            throw new RuntimeException('Staged application path must be inside the staging directory.');
        }

        if (!is_file($realApplicationPath.'/composer.json')) {
            throw new RuntimeException('Staged application path is missing composer.json.');
        }

        return $realApplicationPath;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function buildBackupPath(array $metadata): string
    {
        $manifest = $metadata['manifest'] ?? [];
        $version = \is_array($manifest) && \is_string($manifest['version'] ?? null) ? $manifest['version'] : 'unknown';
        $version = preg_replace('/[^A-Za-z0-9._-]/', '_', $version) ?: 'unknown';

        return $this->projectDir.'/var/update/backups/'.$version.'-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(4));
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     */
    private function checkUpdateLock(string $lockPath, array &$checks): void
    {
        if (is_file($lockPath)) {
            $this->addCheck($checks, 'update_lock', 'failed', 'An update lock file already exists. Another update may be in progress.', [
                'lock_path' => $lockPath,
            ]);

            return;
        }

        $lockDirectory = \dirname($lockPath);

        if (!is_dir($lockDirectory) || !is_writable($lockDirectory)) {
            $this->addCheck($checks, 'update_lock', 'failed', 'Update lock directory is not writable.', [
                'lock_directory' => $lockDirectory,
            ]);

            return;
        }

        $this->addCheck($checks, 'update_lock', 'passed', 'No update lock is currently active.', [
            'lock_path' => $lockPath,
        ]);
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     */
    private function checkBackupReadiness(string $backupPath, array &$checks): void
    {
        $backupBaseDirectory = \dirname($backupPath);

        if (is_dir($backupBaseDirectory)) {
            if (!is_writable($backupBaseDirectory)) {
                $this->addCheck($checks, 'backup_directory', 'failed', 'Update backup directory is not writable.', [
                    'backup_directory' => $backupBaseDirectory,
                ]);

                return;
            }

            $this->addCheck($checks, 'backup_directory', 'passed', 'Update backup directory is writable.', [
                'backup_directory' => $backupBaseDirectory,
                'planned_backup_path' => $backupPath,
            ]);

            return;
        }

        $parentDirectory = $this->findExistingParentDirectory($backupBaseDirectory);

        if (null === $parentDirectory || !is_writable($parentDirectory)) {
            $this->addCheck($checks, 'backup_directory', 'failed', 'Update backup directory cannot be created by the current process.', [
                'backup_directory' => $backupBaseDirectory,
                'existing_parent' => $parentDirectory,
            ]);

            return;
        }

        $this->addCheck($checks, 'backup_directory', 'passed', 'Update backup directory can be created.', [
            'backup_directory' => $backupBaseDirectory,
            'planned_backup_path' => $backupPath,
        ]);
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     */
    private function checkUpdateWorkingDirectories(string $stagingPath, string $lockPath, string $backupPath, array &$checks): void
    {
        $directories = [
            'update_base' => $this->projectDir.'/var/update',
            'staging_directory' => $stagingPath,
            'backup_base' => \dirname($backupPath),
            'logs_directory' => $this->projectDir.'/var/update/logs',
            'operations_directory' => $this->projectDir.'/var/update/operations',
            'lock_directory' => \dirname($lockPath),
        ];

        $unavailableDirectories = [];
        foreach ($directories as $key => $directory) {
            if ($this->canUseDirectory($directory)) {
                continue;
            }

            $unavailableDirectories[$key] = [
                'path' => $directory,
                'existing_parent' => $this->findExistingParentDirectory($directory),
            ];
        }

        if ([] !== $unavailableDirectories) {
            $this->addCheck($checks, 'update_working_directories', 'failed', 'Some update working directories are not writable or cannot be created by the current process.', [
                'directories' => $unavailableDirectories,
            ]);

            return;
        }

        $this->addCheck($checks, 'update_working_directories', 'passed', 'Update working directories are writable or can be created.', [
            'directories' => $directories,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFilePlan(string $applicationPath): array
    {
        $filesTotal = 0;
        $filesToReplace = 0;
        $filesNew = 0;
        $directoriesTotal = 0;
        $directoriesNew = 0;
        $skipped = [];
        $replaceSamples = [];
        $newSamples = [];
        $directorySamples = [];
        $unwritableTargets = [];
        $symlinkTargets = [];
        $migrationFilesNew = [];
        $migrationFilesToReplace = [];
        $unsupportedMigrationFilesNew = [];
        $truncatedLists = false;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($applicationPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $relativePath = $this->normalizeRelativePath(substr($item->getPathname(), \strlen($applicationPath) + 1));

            if ('' === $relativePath) {
                continue;
            }

            if ($this->shouldSkipPath($relativePath)) {
                $this->appendLimited($skipped, $relativePath, $truncatedLists);

                continue;
            }

            $targetPath = $this->projectDir.'/'.$relativePath;
            $isSupportedMigrationPath = $this->isSupportedMigrationPath($relativePath);
            $isUnsupportedMigrationPath = $this->isUnsupportedMigrationPath($relativePath);

            if ($item->isDir()) {
                $directoriesTotal++;

                if (!is_dir($targetPath)) {
                    $directoriesNew++;
                    $this->appendLimited($directorySamples, $relativePath, $truncatedLists);

                    if (!$this->canCreatePath($targetPath)) {
                        $this->appendLimited($unwritableTargets, $relativePath, $truncatedLists);
                    }
                }

                continue;
            }

            if (!$item->isFile()) {
                $this->appendLimited($skipped, $relativePath, $truncatedLists);

                continue;
            }

            $filesTotal++;

            if (is_link($targetPath)) {
                $this->appendLimited($symlinkTargets, $relativePath, $truncatedLists);

                continue;
            }

            if (is_file($targetPath)) {
                $filesToReplace++;
                $this->appendLimited($replaceSamples, $relativePath, $truncatedLists);

                if ($isSupportedMigrationPath || $isUnsupportedMigrationPath) {
                    $this->appendLimited($migrationFilesToReplace, $relativePath, $truncatedLists);
                }

                if (!is_writable($targetPath)) {
                    $this->appendLimited($unwritableTargets, $relativePath, $truncatedLists);
                }

                continue;
            }

            $filesNew++;
            $this->appendLimited($newSamples, $relativePath, $truncatedLists);

            if ($isSupportedMigrationPath) {
                $this->appendLimited($migrationFilesNew, $relativePath, $truncatedLists);
            }

            if ($isUnsupportedMigrationPath) {
                $this->appendLimited($unsupportedMigrationFilesNew, $relativePath, $truncatedLists);
            }

            if (!$this->canCreatePath($targetPath)) {
                $this->appendLimited($unwritableTargets, $relativePath, $truncatedLists);
            }
        }

        return [
            'files_total' => $filesTotal,
            'files_to_replace' => $filesToReplace,
            'files_new' => $filesNew,
            'directories_total' => $directoriesTotal,
            'directories_new' => $directoriesNew,
            'skipped_paths_sample' => $skipped,
            'files_to_replace_sample' => $replaceSamples,
            'files_new_sample' => $newSamples,
            'directories_new_sample' => $directorySamples,
            'unwritable_targets_sample' => array_values(array_unique($unwritableTargets)),
            'symlink_targets_sample' => array_values(array_unique($symlinkTargets)),
            'migration_files_new' => array_values(array_unique($migrationFilesNew)),
            'migration_files_to_replace_sample' => array_values(array_unique($migrationFilesToReplace)),
            'unsupported_migration_files_new' => array_values(array_unique($unsupportedMigrationFilesNew)),
            'lists_truncated' => $truncatedLists,
            'skipped_top_level_entries' => self::SKIPPED_PREFIXES,
            'skipped_exact_paths' => self::SKIPPED_EXACT_PATHS,
        ];
    }

    /**
     * @param array<string, mixed> $filePlan
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $warnings
     */
    private function addFilePlanChecks(array $filePlan, array &$checks, array &$warnings): void
    {
        $this->addCheck($checks, 'file_plan', 'passed', 'Update apply file plan was generated.', [
            'files_total' => $filePlan['files_total'] ?? 0,
            'files_to_replace' => $filePlan['files_to_replace'] ?? 0,
            'files_new' => $filePlan['files_new'] ?? 0,
            'directories_new' => $filePlan['directories_new'] ?? 0,
        ]);

        $unwritableTargets = $filePlan['unwritable_targets_sample'] ?? [];
        if (\is_array($unwritableTargets) && [] !== $unwritableTargets) {
            $this->addCheck($checks, 'write_permissions', 'failed', 'Some target files or directories are not writable.', [
                'unwritable_targets_sample' => $unwritableTargets,
            ]);
        } else {
            $this->addCheck($checks, 'write_permissions', 'passed', 'Target files and directories appear writable for the planned update.');
        }

        $symlinkTargets = $filePlan['symlink_targets_sample'] ?? [];
        if (\is_array($symlinkTargets) && [] !== $symlinkTargets) {
            $this->addCheck($checks, 'symlink_targets', 'failed', 'The planned update would overwrite existing symbolic links.', [
                'symlink_targets_sample' => $symlinkTargets,
            ]);
        } else {
            $this->addCheck($checks, 'symlink_targets', 'passed', 'The planned update does not overwrite existing symbolic links.');
        }

        $unsupportedMigrationFiles = $filePlan['unsupported_migration_files_new'] ?? [];
        if (\is_array($unsupportedMigrationFiles) && [] !== $unsupportedMigrationFiles) {
            $this->addCheck($checks, 'unsupported_migration_paths', 'failed', 'New update migration files must be placed under src/CoreBundle/Migrations/Schema/V210.', [
                'unsupported_migration_files_new' => $unsupportedMigrationFiles,
            ]);
        }

        $migrationFilesNew = $filePlan['migration_files_new'] ?? [];
        if (\is_array($migrationFilesNew) && [] !== $migrationFilesNew) {
            $warnings[] = 'New V210 Doctrine migration files were detected. They will require a database migration safety review after applying files.';
        }

        if (true === ($filePlan['lists_truncated'] ?? false)) {
            $warnings[] = 'Some update apply plan file lists were truncated in the JSON response.';
        }
    }

    private function shouldSkipPath(string $relativePath): bool
    {
        if (\in_array($relativePath, self::SKIPPED_EXACT_PATHS, true)) {
            return true;
        }

        foreach (self::SKIPPED_PREFIXES as $prefix) {
            if ($relativePath === $prefix || str_starts_with($relativePath, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    private function isSupportedMigrationPath(string $relativePath): bool
    {
        return 1 === preg_match('/^src\/CoreBundle\/Migrations\/Schema\/V210\/Version[0-9]+\.php$/', $relativePath);
    }

    private function isUnsupportedMigrationPath(string $relativePath): bool
    {
        return 1 === preg_match('/^src\/CoreBundle\/Migrations\/Schema\/V[0-9]+\/Version[0-9]+\.php$/', $relativePath)
            && !$this->isSupportedMigrationPath($relativePath);
    }

    private function normalizeRelativePath(string $relativePath): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');

        if (str_contains($relativePath, "\0") || str_contains($relativePath, '../') || str_starts_with($relativePath, '../')) {
            throw new RuntimeException('Unsafe staged relative path detected: '.$relativePath);
        }

        return $relativePath;
    }

    private function canCreatePath(string $targetPath): bool
    {
        $parentDirectory = $this->findExistingParentDirectory(\dirname($targetPath));

        return null !== $parentDirectory && is_writable($parentDirectory);
    }

    private function canUseDirectory(string $directory): bool
    {
        if (is_dir($directory)) {
            return is_writable($directory);
        }

        $parentDirectory = $this->findExistingParentDirectory($directory);

        return null !== $parentDirectory && is_writable($parentDirectory);
    }

    private function findExistingParentDirectory(string $path): ?string
    {
        $path = rtrim($path, '/');

        while ('' !== $path && !is_dir($path)) {
            $parent = \dirname($path);

            if ($parent === $path) {
                return null;
            }

            $path = $parent;
        }

        return is_dir($path) ? $path : null;
    }

    /**
     * @param string[] $items
     */
    private function appendLimited(array &$items, string $value, bool &$truncatedLists): void
    {
        if (\count($items) >= self::FILE_LIST_LIMIT) {
            $truncatedLists = true;

            return;
        }

        $items[] = $value;
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    private function summarizeStagingMetadata(array $metadata): array
    {
        $manifest = $metadata['manifest'] ?? [];

        return [
            'created_at' => $metadata['created_at'] ?? null,
            'manifest_version' => \is_array($manifest) ? ($manifest['version'] ?? null) : null,
            'manifest_channel' => \is_array($manifest) ? ($manifest['channel'] ?? null) : null,
            'package_path' => $metadata['package_path'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $filePlan
     */
    private function writeApplyPlanMetadata(
        string $stagingPath,
        array $metadata,
        string $applicationPath,
        string $backupPath,
        string $lockPath,
        array $filePlan
    ): void {
        $applyPlan = [
            'created_at' => gmdate('c'),
            'application_path' => $applicationPath,
            'backup_path' => $backupPath,
            'lock_path' => $lockPath,
            'manifest' => $metadata['manifest'] ?? [],
            'file_plan' => $filePlan,
            'note' => 'This file is an apply plan only. No update files have been copied to the Chamilo installation.',
        ];

        $encoded = json_encode($applyPlan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (false === file_put_contents($stagingPath.'/APPLY-PLAN.json', $encoded.PHP_EOL)) {
            throw new RuntimeException('Unable to write update apply plan metadata.');
        }
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     *
     * @return string[]
     */
    private function collectFailedCheckMessages(array $checks): array
    {
        $errors = [];

        foreach ($checks as $check) {
            if ('failed' === ($check['status'] ?? null)) {
                $errors[] = (string) ($check['message'] ?? 'Update apply plan check failed.');
            }
        }

        return $errors;
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param array<string, mixed> $details
     */
    private function addCheck(array &$checks, string $key, string $status, string $message, array $details = []): void
    {
        $check = [
            'key' => $key,
            'status' => $status,
            'message' => $message,
        ];

        if ([] !== $details) {
            $check['details'] = $details;
        }

        $checks[] = $check;
    }

    private function isPathInside(string $path, string $basePath): bool
    {
        $path = rtrim($path, '/');
        $basePath = rtrim($basePath, '/');

        return $path === $basePath || str_starts_with($path, $basePath.'/');
    }
}
