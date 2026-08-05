<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateApplyFilesResult;
use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

final readonly class UpdateFileApplier
{
    private const array SKIPPED_EXACT_PATHS = [
        '.env',
        '.env.local',
        'app/config/configuration.php',
    ];
    private const array SKIPPED_PREFIXES = [
        '.git',
        'node_modules',
        'vendor',
        'var',
        'public/courses',
        'public/upload',
    ];
    private const string AUDIT_FILE_NAME = 'APPLY-RESULT.json';

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
        private UpdateOperationLogger $operationLogger,
        private UpdateConfiguration $updateConfiguration,
    ) {}

    public function apply(string $stagingPath, bool $confirmed, ?string $operationId = null): UpdateApplyFilesResult
    {
        $checks = [];
        $warnings = [];
        $details = [
            'project_dir' => $this->projectDir,
            'requested_staging_path' => $stagingPath,
            'confirmed' => $confirmed,
            'operation_id' => $operationId,
        ];

        if (null !== $operationId && '' !== trim($operationId)) {
            $operationId = $this->operationLogger->create($operationId);
            $this->logOperation($operationId, 'info', 'start', 'Starting staged file application.');
        }

        if (!$confirmed) {
            $this->logOperation($operationId, 'error', 'failed', 'Explicit confirmation is required to apply staged files.');

            return UpdateApplyFilesResult::failure([
                'Applying staged update files requires an explicit confirmation.',
            ], $checks, $warnings, $details);
        }

        $lockAcquired = false;
        $lockPath = null;
        $backupPath = null;
        $appliedFiles = [];
        $fileOperations = [];
        $auditPath = null;

        try {
            $stagingPath = $this->resolveSafeStagingPath($stagingPath);
            $details['staging_path'] = $stagingPath;
            $this->addCheck($checks, 'staging_path', 'passed', 'Staging directory is inside the Chamilo update staging directory.', [
                'staging_path' => $stagingPath,
            ]);
            $this->logOperation($operationId, 'success', 'staging_path', 'Staging directory was validated.');

            $applyPlan = $this->readApplyPlan($stagingPath);
            $details['apply_plan'] = $this->summarizeApplyPlan($applyPlan);
            $this->addCheck($checks, 'apply_plan_metadata', 'passed', 'Apply plan metadata was read successfully.');
            $this->logOperation($operationId, 'success', 'apply_plan', 'Apply plan metadata was read successfully.');

            $applicationPath = $this->resolveApplicationPath($stagingPath, $applyPlan);
            $details['application_path'] = $applicationPath;
            $this->addCheck($checks, 'application_path', 'passed', 'Staged application path is valid.', [
                'application_path' => $applicationPath,
            ]);
            $this->logOperation($operationId, 'success', 'application_path', 'Staged application path is valid.');

            $lockPath = $this->resolveLockPath($applyPlan);
            $details['lock_path'] = $lockPath;
            $this->acquireLock($lockPath);
            $lockAcquired = true;
            $this->addCheck($checks, 'update_lock', 'passed', 'Update lock was acquired.', [
                'lock_path' => $lockPath,
            ]);
            $this->logOperation($operationId, 'success', 'update_lock', 'Update lock was acquired.');

            $backupPath = $this->resolveBackupPath($applyPlan);
            $details['backup_path'] = $backupPath;
            $this->prepareBackupDirectory($backupPath);
            $this->addCheck($checks, 'backup_directory', 'passed', 'Update backup directory was created.', [
                'backup_path' => $backupPath,
            ]);
            $this->logOperation($operationId, 'success', 'backup_directory', 'Update backup directory was created.');

            $fileOperations = $this->collectFileOperations($applicationPath);
            $details['file_operations'] = $this->summarizeFileOperations($fileOperations);
            $this->addCheck($checks, 'file_plan', 'passed', 'Update file operations were collected.', $details['file_operations']);
            $this->logOperation($operationId, 'info', 'file_plan', 'Update file operations were collected.');

            $this->backupExistingFiles($fileOperations, $backupPath);
            $this->addCheck($checks, 'file_backup', 'passed', 'Existing files planned for replacement were backed up.', [
                'files_backed_up' => $details['file_operations']['files_to_replace'] ?? 0,
            ]);
            $this->logOperation($operationId, 'success', 'file_backup', 'Existing files planned for replacement were backed up.');

            $appliedFiles = $this->copyStagedFiles($fileOperations, $operationId);
            $this->addCheck($checks, 'file_copy', 'passed', 'Staged update files were copied to the Chamilo installation.', [
                'files_copied' => \count($appliedFiles),
            ]);
            $this->logOperation($operationId, 'success', 'file_copy', 'Staged update files were copied to the Chamilo installation.');

            $auditPath = $this->writeAuditFile($stagingPath, $backupPath, $lockPath, $checks, $warnings, $details, true);
            $this->addCheck($checks, 'apply_audit', 'passed', 'Update apply audit metadata was written.', [
                'audit_path' => $auditPath,
            ]);
            $this->logOperation($operationId, 'success', 'apply_audit', 'Update apply audit metadata was written.');

            $this->releaseLock($lockPath);
            $lockAcquired = false;
            $this->logOperation($operationId, 'success', 'done', 'Staged files applied successfully.');

            return UpdateApplyFilesResult::success($stagingPath, $backupPath, $lockPath, $auditPath, $checks, $warnings, $details);
        } catch (Throwable $exception) {
            $errors = [$exception->getMessage()];
            $details['exception'] = $exception::class;
            $this->logOperation($operationId, 'error', 'failed', $exception->getMessage());

            if ([] !== $appliedFiles && null !== $backupPath) {
                try {
                    $this->rollbackAppliedFiles($appliedFiles, $backupPath);
                    $warnings[] = 'File rollback was executed after the update apply failure.';
                    $details['rollback_executed'] = true;
                    $this->logOperation($operationId, 'warning', 'rollback', 'File rollback was executed after the update apply failure.');
                } catch (Throwable $rollbackException) {
                    $errors[] = 'Rollback failed: '.$rollbackException->getMessage();
                    $details['rollback_exception'] = $rollbackException::class;
                    $details['rollback_executed'] = false;
                }
            }

            if (null !== $stagingPath && is_dir($stagingPath) && null !== $backupPath && null !== $lockPath) {
                try {
                    $auditPath = $this->writeAuditFile($stagingPath, $backupPath, $lockPath, $checks, $warnings, $details, false, $errors);
                    $details['audit_path'] = $auditPath;
                } catch (Throwable $auditException) {
                    $warnings[] = 'Unable to write update apply audit metadata: '.$auditException->getMessage();
                }
            }

            if ($lockAcquired && null !== $lockPath) {
                try {
                    $this->releaseLock($lockPath);
                } catch (Throwable $lockException) {
                    $warnings[] = 'Unable to release update lock after failure: '.$lockException->getMessage();
                }
            }

            return UpdateApplyFilesResult::failure($errors, $checks, $warnings, $details);
        }
    }

    private function resolveSafeStagingPath(string $stagingPath): string
    {
        $stagingPath = rtrim(trim($stagingPath), '/');

        if ('' === $stagingPath) {
            throw new RuntimeException('Staging path is required to apply update files.');
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

        if (!is_file($realStagingPath.'/APPLY-PLAN.json')) {
            throw new RuntimeException('Staging directory is missing APPLY-PLAN.json.');
        }

        return $realStagingPath;
    }

    /**
     * @return array<string, mixed>
     */
    private function readApplyPlan(string $stagingPath): array
    {
        $applyPlanPath = $stagingPath.'/APPLY-PLAN.json';
        $content = file_get_contents($applyPlanPath);

        if (false === $content) {
            throw new RuntimeException('Unable to read update apply plan metadata: '.$applyPlanPath);
        }

        try {
            $applyPlan = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Update apply plan metadata is not valid JSON: '.$exception->getMessage(), 0, $exception);
        }

        if (!\is_array($applyPlan)) {
            throw new RuntimeException('Update apply plan metadata must be a JSON object.');
        }

        return $applyPlan;
    }

    /**
     * @param array<string, mixed> $applyPlan
     */
    private function resolveApplicationPath(string $stagingPath, array $applyPlan): string
    {
        $applicationPath = $applyPlan['application_path'] ?? null;

        if (!\is_string($applicationPath) || '' === trim($applicationPath)) {
            throw new RuntimeException('Update apply plan is missing the application_path field.');
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
     * @param array<string, mixed> $applyPlan
     */
    private function resolveLockPath(array $applyPlan): string
    {
        $lockPath = $applyPlan['lock_path'] ?? null;

        if (!\is_string($lockPath) || '' === trim($lockPath)) {
            $lockPath = $this->projectDir.'/var/update/update.lock';
        }

        $lockPath = rtrim($lockPath, '/');
        $lockDirectory = realpath(\dirname($lockPath));

        if (false === $lockDirectory || !$this->isPathInside($lockDirectory, $this->projectDir.'/var/update')) {
            throw new RuntimeException('Update lock path must be inside var/update.');
        }

        return $lockPath;
    }

    /**
     * @param array<string, mixed> $applyPlan
     */
    private function resolveBackupPath(array $applyPlan): string
    {
        $backupPath = $applyPlan['backup_path'] ?? null;

        if (!\is_string($backupPath) || '' === trim($backupPath)) {
            throw new RuntimeException('Update apply plan is missing the backup_path field.');
        }

        $backupPath = rtrim(str_replace('\\', '/', $backupPath), '/');

        if (str_contains($backupPath, "\0") || str_contains('/'.$backupPath.'/', '/../')) {
            throw new RuntimeException('Update backup path contains unsafe path segments.');
        }

        $backupBasePath = realpath($this->projectDir.'/var/update/backups');

        if (false === $backupBasePath) {
            $this->ensureDirectory($this->projectDir.'/var/update/backups');
            $backupBasePath = realpath($this->projectDir.'/var/update/backups');
        }

        if (false === $backupBasePath || !$this->isPathInside(\dirname($backupPath), $backupBasePath)) {
            throw new RuntimeException('Update backup path must be inside var/update/backups.');
        }

        return $backupPath;
    }

    private function acquireLock(string $lockPath): void
    {
        $this->ensureDirectory(\dirname($lockPath));

        $handle = @fopen($lockPath, 'x');

        if (false === $handle) {
            throw new RuntimeException('Unable to acquire update lock. Another update may be in progress.');
        }

        fwrite($handle, json_encode([
            'created_at' => gmdate('c'),
            'process_id' => getmypid(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
        fclose($handle);
    }

    private function releaseLock(string $lockPath): void
    {
        if (is_file($lockPath) && !@unlink($lockPath)) {
            throw new RuntimeException('Unable to release update lock: '.$lockPath);
        }
    }

    private function prepareBackupDirectory(string $backupPath): void
    {
        if (is_dir($backupPath)) {
            throw new RuntimeException('Update backup directory already exists: '.$backupPath);
        }

        $this->ensureDirectory($backupPath.'/files');
    }

    /**
     * @return array<int, array{relative_path: string, source_path: string, target_path: string, exists: bool}>
     */
    private function collectFileOperations(string $applicationPath): array
    {
        $operations = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($applicationPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $relativePath = $this->normalizeRelativePath(substr($item->getPathname(), \strlen($applicationPath) + 1));

            if ('' === $relativePath || $this->shouldSkipPath($relativePath)) {
                continue;
            }

            $targetPath = $this->projectDir.'/'.$relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath) && !$this->canCreatePath($targetPath)) {
                    throw new RuntimeException('Target directory cannot be created: '.$relativePath);
                }

                continue;
            }

            if (!$item->isFile()) {
                continue;
            }

            if (is_link($targetPath)) {
                throw new RuntimeException('The planned update would overwrite an existing symbolic link: '.$relativePath);
            }

            if (is_file($targetPath) && !is_writable($targetPath)) {
                throw new RuntimeException('Target file is not writable: '.$relativePath);
            }

            if (!is_file($targetPath) && !$this->canCreatePath($targetPath)) {
                throw new RuntimeException('Target file cannot be created: '.$relativePath);
            }

            $operations[] = [
                'relative_path' => $relativePath,
                'source_path' => $item->getPathname(),
                'target_path' => $targetPath,
                'exists' => is_file($targetPath),
            ];
        }

        return $operations;
    }

    /**
     * @param array<int, array{relative_path: string, source_path: string, target_path: string, exists: bool}> $operations
     *
     * @return array<string, int|string[]|bool>
     */
    private function summarizeFileOperations(array $operations): array
    {
        $filesToReplace = 0;
        $filesNew = 0;
        $replaceSamples = [];
        $newSamples = [];

        foreach ($operations as $operation) {
            if ($operation['exists']) {
                $filesToReplace++;
                $this->appendSample($replaceSamples, $operation['relative_path']);

                continue;
            }

            $filesNew++;
            $this->appendSample($newSamples, $operation['relative_path']);
        }

        return [
            'files_total' => \count($operations),
            'files_to_replace' => $filesToReplace,
            'files_new' => $filesNew,
            'files_to_replace_sample' => $replaceSamples,
            'files_new_sample' => $newSamples,
            'samples_truncated' => \count($replaceSamples) + \count($newSamples) < \count($operations),
        ];
    }

    /**
     * @param array<int, array{relative_path: string, source_path: string, target_path: string, exists: bool}> $operations
     */
    private function backupExistingFiles(array $operations, string $backupPath): void
    {
        foreach ($operations as $operation) {
            if (!$operation['exists']) {
                continue;
            }

            $backupFilePath = $backupPath.'/files/'.$operation['relative_path'];
            $this->ensureDirectory(\dirname($backupFilePath));

            if (!copy($operation['target_path'], $backupFilePath)) {
                throw new RuntimeException('Unable to back up target file: '.$operation['relative_path']);
            }
        }

        $metadata = [
            'created_at' => gmdate('c'),
            'project_dir' => $this->projectDir,
            'files_total' => \count($operations),
            'files_to_replace' => \count(array_filter($operations, static fn (array $operation): bool => true === $operation['exists'])),
            'files_new' => \count(array_filter($operations, static fn (array $operation): bool => false === $operation['exists'])),
        ];

        $encoded = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (false === file_put_contents($backupPath.'/BACKUP-INFO.json', $encoded.PHP_EOL)) {
            throw new RuntimeException('Unable to write update backup metadata.');
        }
    }

    /**
     * @param array<int, array{relative_path: string, source_path: string, target_path: string, exists: bool}> $operations
     *
     * @return array<int, array{relative_path: string, target_path: string, existed: bool}>
     */
    private function copyStagedFiles(array $operations, ?string $operationId): array
    {
        $appliedFiles = [];
        $totalOperations = \count($operations);
        $copiedFiles = 0;
        $debugSlowCopyMilliseconds = $this->updateConfiguration->getDebugSlowCopyMilliseconds();

        foreach ($operations as $operation) {
            $this->ensureDirectory(\dirname($operation['target_path']));

            if (!copy($operation['source_path'], $operation['target_path'])) {
                throw new RuntimeException('Unable to copy staged update file: '.$operation['relative_path']);
            }

            $appliedFiles[] = [
                'relative_path' => $operation['relative_path'],
                'target_path' => $operation['target_path'],
                'existed' => $operation['exists'],
            ];
            $copiedFiles++;

            if (1 === $copiedFiles || $copiedFiles === $totalOperations || 0 === $copiedFiles % 25) {
                $this->logOperation(
                    $operationId,
                    'info',
                    'file_copy_progress',
                    'Copied '.$copiedFiles.' of '.$totalOperations.' staged files.'
                );
            }

            if ($debugSlowCopyMilliseconds > 0) {
                usleep($debugSlowCopyMilliseconds * 1000);
            }
        }

        return $appliedFiles;
    }

    /**
     * @param array<int, array{relative_path: string, target_path: string, existed: bool}> $appliedFiles
     */
    private function rollbackAppliedFiles(array $appliedFiles, string $backupPath): void
    {
        foreach (array_reverse($appliedFiles) as $appliedFile) {
            $relativePath = $appliedFile['relative_path'];
            $targetPath = $appliedFile['target_path'];

            if ($appliedFile['existed']) {
                $backupFilePath = $backupPath.'/files/'.$relativePath;

                if (!is_file($backupFilePath) || !copy($backupFilePath, $targetPath)) {
                    throw new RuntimeException('Unable to restore backed up file: '.$relativePath);
                }

                continue;
            }

            if (is_file($targetPath) && !@unlink($targetPath)) {
                throw new RuntimeException('Unable to remove newly copied file during rollback: '.$relativePath);
            }
        }
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[]                                                                                        $warnings
     * @param array<string, mixed>                                                                            $details
     * @param string[]                                                                                        $errors
     */
    private function writeAuditFile(
        string $stagingPath,
        string $backupPath,
        string $lockPath,
        array $checks,
        array $warnings,
        array $details,
        bool $success,
        array $errors = [],
    ): string {
        $audit = [
            'created_at' => gmdate('c'),
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings,
            'staging_path' => $stagingPath,
            'backup_path' => $backupPath,
            'lock_path' => $lockPath,
            'checks' => $checks,
            'details' => $details,
            'note' => 'This file records the file-copy step only. Database migrations, Composer, Yarn and cache commands are not executed here.',
        ];
        $encoded = json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $auditPath = $stagingPath.'/'.self::AUDIT_FILE_NAME;

        if (false === file_put_contents($auditPath, $encoded.PHP_EOL)) {
            throw new RuntimeException('Unable to write update apply audit metadata.');
        }

        $this->writeGlobalAuditLog($audit);

        return $auditPath;
    }

    /**
     * @param array<string, mixed> $audit
     */
    private function writeGlobalAuditLog(array $audit): void
    {
        $logDirectory = $this->projectDir.'/var/update/logs';
        $this->ensureDirectory($logDirectory);

        $logPath = $logDirectory.'/apply-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(4)).'.json';
        $encoded = json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (false === file_put_contents($logPath, $encoded.PHP_EOL)) {
            throw new RuntimeException('Unable to write global update apply log.');
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

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            if (!is_writable($directory)) {
                throw new RuntimeException('Directory is not writable: '.$directory);
            }

            return;
        }

        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create directory: '.$directory);
        }
    }

    /**
     * @param string[] $samples
     */
    private function appendSample(array &$samples, string $value): void
    {
        if (\count($samples) >= 20) {
            return;
        }

        $samples[] = $value;
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logOperation(?string $operationId, string $level, string $step, string $message, array $details = []): void
    {
        if (null === $operationId || '' === trim($operationId)) {
            return;
        }

        try {
            $this->operationLogger->append($operationId, $level, $step, $message, $details);
        } catch (Throwable) {
            // Operation logging must never interrupt the update file application flow.
        }
    }

    /**
     * @param array<string, mixed> $applyPlan
     *
     * @return array<string, mixed>
     */
    private function summarizeApplyPlan(array $applyPlan): array
    {
        $manifest = $applyPlan['manifest'] ?? [];
        $filePlan = $applyPlan['file_plan'] ?? [];

        return [
            'created_at' => $applyPlan['created_at'] ?? null,
            'manifest_version' => \is_array($manifest) ? ($manifest['version'] ?? null) : null,
            'manifest_channel' => \is_array($manifest) ? ($manifest['channel'] ?? null) : null,
            'files_total' => \is_array($filePlan) ? ($filePlan['files_total'] ?? null) : null,
            'files_to_replace' => \is_array($filePlan) ? ($filePlan['files_to_replace'] ?? null) : null,
            'files_new' => \is_array($filePlan) ? ($filePlan['files_new'] ?? null) : null,
        ];
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param array<string, mixed>                                                                            $details
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
