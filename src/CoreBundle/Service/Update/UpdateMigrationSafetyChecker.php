<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateMigrationSafetyCheckResult;
use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;
use Throwable;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

final readonly class UpdateMigrationSafetyChecker
{
    private const METADATA_FILE_NAME = 'MIGRATION-SAFETY-CHECKS.json';
    private const DRY_RUN_OUTPUT_LIMIT = 20000;

    private const MIGRATION_PREFIXES = [
        'src/CoreBundle/Migrations/Schema/V200/',
        'src/CoreBundle/Migrations/Schema/V210/',
    ];

    public function __construct(
        private UpdateConfiguration $updateConfiguration,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {}

    public function check(string $stagingPath): UpdateMigrationSafetyCheckResult
    {
        $checks = [];
        $warnings = [
            'Database backup is not managed by this updater. Create and verify a database backup before running migrations.',
            'Doctrine migration down() methods are not guaranteed to restore deleted or transformed data.',
        ];
        $details = [
            'project_dir' => $this->projectDir,
            'requested_staging_path' => $stagingPath,
        ];
        $dryRunCommand = 'php bin/console doctrine:migrations:migrate --dry-run --no-interaction';
        $dryRunExitCode = null;
        $dryRunOutput = '';

        try {
            $stagingPath = $this->resolveSafeStagingPath($stagingPath);
            $details['staging_path'] = $stagingPath;
            $this->addCheck($checks, 'staging_path', 'passed', 'Staging directory is inside the Chamilo update staging directory.', [
                'staging_path' => $stagingPath,
            ]);

            $postApplyChecks = $this->readJsonFile($stagingPath.'/POST-APPLY-CHECKS.json', 'post-apply checks');
            $this->assertDatabaseMigrationsWereRecommended($postApplyChecks);
            $this->addCheck($checks, 'post_apply_metadata', 'passed', 'Post-apply metadata recommends database migration review.');

            $stagingMetadata = $this->readJsonFile($stagingPath.'/STAGING-INFO.json', 'staging metadata');
            $applyPlan = $this->readJsonFile($stagingPath.'/APPLY-PLAN.json', 'apply plan metadata');
            $applicationPath = $this->resolveApplicationPath($stagingPath, $applyPlan, $stagingMetadata);
            $details['application_path'] = $applicationPath;
            $this->addCheck($checks, 'application_path', 'passed', 'Staged application path is valid.', [
                'application_path' => $applicationPath,
            ]);

            $migrations = $this->collectStagedMigrations($applicationPath);
            $details['migration_count'] = \count($migrations);
            $details['migration_classes'] = array_map(static fn (array $migration): string => $migration['class'], $migrations);

            if ([] === $migrations) {
                $this->addCheck($checks, 'migration_files', 'failed', 'No migration files were found in the staged package.');

                return UpdateMigrationSafetyCheckResult::failure(
                    $this->collectFailedCheckMessages($checks),
                    $stagingPath,
                    null,
                    $checks,
                    $warnings,
                    $details,
                    $dryRunCommand,
                    null,
                    ''
                );
            }

            $this->addCheck($checks, 'migration_files', 'passed', 'Staged Doctrine migration files were detected.', [
                'migration_count' => \count($migrations),
                'migration_classes' => $details['migration_classes'],
            ]);

            [$dryRunExitCode, $dryRunOutput] = $this->runDoctrineDryRun($dryRunCommand);
            $details['dry_run_exit_code'] = $dryRunExitCode;

            if (0 !== $dryRunExitCode) {
                $this->addCheck($checks, 'migration_dry_run', 'failed', 'Doctrine migration dry-run failed. Do not run database migrations until this is fixed.', [
                    'exit_code' => $dryRunExitCode,
                ]);

                $metadataPath = $this->writeMetadata($stagingPath, false, $checks, $warnings, $details, $migrations, $dryRunCommand, $dryRunExitCode, $dryRunOutput);

                return UpdateMigrationSafetyCheckResult::failure(
                    $this->collectFailedCheckMessages($checks),
                    $stagingPath,
                    $metadataPath,
                    $checks,
                    $warnings,
                    $details,
                    $dryRunCommand,
                    $dryRunExitCode,
                    $dryRunOutput
                );
            }

            $this->addCheck($checks, 'migration_dry_run', 'passed', 'Doctrine migration dry-run completed successfully.', [
                'command' => $dryRunCommand,
                'exit_code' => $dryRunExitCode,
            ]);

            $this->addCheck($checks, 'database_backup_notice', 'warning', 'A database backup must be created outside this updater before running migrations.');

            $metadataPath = $this->writeMetadata($stagingPath, true, $checks, $warnings, $details, $migrations, $dryRunCommand, $dryRunExitCode, $dryRunOutput);

            return UpdateMigrationSafetyCheckResult::success(
                $stagingPath,
                $metadataPath,
                $migrations,
                $checks,
                $warnings,
                $details,
                $dryRunCommand,
                $dryRunExitCode,
                $dryRunOutput
            );
        } catch (RuntimeException $exception) {
            return UpdateMigrationSafetyCheckResult::failure(
                [$exception->getMessage()],
                isset($stagingPath) && \is_string($stagingPath) ? $stagingPath : null,
                null,
                $checks,
                $warnings,
                $details,
                $dryRunCommand,
                $dryRunExitCode,
                $dryRunOutput
            );
        }
    }

    private function resolveSafeStagingPath(string $stagingPath): string
    {
        $stagingPath = rtrim(trim($stagingPath), '/');

        if ('' === $stagingPath) {
            throw new RuntimeException('Staging path is required to review database migrations.');
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

        return $realStagingPath;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonFile(string $path, string $label): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Unable to read update '.$label.': '.$path);
        }

        $content = file_get_contents($path);

        if (false === $content) {
            throw new RuntimeException('Unable to read update '.$label.': '.$path);
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Update '.$label.' JSON is invalid: '.$exception->getMessage(), 0, $exception);
        }

        if (!\is_array($data)) {
            throw new RuntimeException('Update '.$label.' JSON must be an object.');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $postApplyChecks
     */
    private function assertDatabaseMigrationsWereRecommended(array $postApplyChecks): void
    {
        $actions = $postApplyChecks['actions'] ?? null;

        if (!\is_array($actions)) {
            throw new RuntimeException('Post-apply checks metadata does not contain a valid actions list.');
        }

        foreach ($actions as $action) {
            if (\is_array($action) && 'database_migrations' === ($action['key'] ?? null)) {
                return;
            }
        }

        throw new RuntimeException('Database migrations were not recommended for this staged update.');
    }

    /**
     * @param array<string, mixed> $applyPlan
     * @param array<string, mixed> $stagingMetadata
     */
    private function resolveApplicationPath(string $stagingPath, array $applyPlan, array $stagingMetadata): string
    {
        $applicationPath = $applyPlan['application_path'] ?? $stagingMetadata['application_path'] ?? null;

        if (!\is_string($applicationPath) || '' === trim($applicationPath)) {
            throw new RuntimeException('Update metadata is missing the staged application path.');
        }

        $realApplicationPath = realpath($applicationPath);

        if (false === $realApplicationPath || !is_dir($realApplicationPath)) {
            throw new RuntimeException('Staged application path is not readable: '.$applicationPath);
        }

        if (!$this->isPathInside($realApplicationPath, $stagingPath)) {
            throw new RuntimeException('Staged application path must be inside the staging directory.');
        }

        return $realApplicationPath;
    }

    /**
     * @return array<int, array{class: string, path: string, description: string, namespace?: string}>
     */
    private function collectStagedMigrations(string $applicationPath): array
    {
        $migrations = [];

        foreach (self::MIGRATION_PREFIXES as $prefix) {
            $directory = $applicationPath.'/'.rtrim($prefix, '/');
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if (!$item->isFile() || 'php' !== strtolower($item->getExtension())) {
                    continue;
                }

                $relativePath = $this->normalizeRelativePath(substr($item->getPathname(), \strlen($applicationPath) + 1));
                if (!preg_match('/(^|\/)Version[0-9]+\.php$/', $relativePath)) {
                    continue;
                }

                $migrations[] = $this->parseMigrationFile($item->getPathname(), $relativePath);
            }
        }

        usort($migrations, static fn (array $left, array $right): int => strcmp($left['class'], $right['class']));

        return $migrations;
    }

    /**
     * @return array{class: string, path: string, description: string, namespace?: string}
     */
    private function parseMigrationFile(string $absolutePath, string $relativePath): array
    {
        $content = file_get_contents($absolutePath);

        if (false === $content) {
            throw new RuntimeException('Unable to read staged migration file: '.$relativePath);
        }

        $namespace = null;
        if (preg_match('/^\s*namespace\s+([^;]+);/m', $content, $matches)) {
            $namespace = trim($matches[1]);
        }

        $class = pathinfo($relativePath, PATHINFO_FILENAME);
        if (preg_match('/^\s*(?:final\s+)?class\s+([A-Za-z0-9_]+)/m', $content, $matches)) {
            $class = trim($matches[1]);
        }

        $fullyQualifiedClass = null !== $namespace ? $namespace.'\\'.$class : $class;

        $description = 'No description could be extracted from getDescription().';
        if (preg_match('/function\s+getDescription\s*\([^)]*\)\s*:\s*string\s*\{(?P<body>.*?)\}/s', $content, $matches)) {
            $body = $matches['body'];
            if (preg_match('/return\s+[\'"](?P<description>.*?)[\'"]\s*;/s', $body, $descriptionMatches)) {
                $description = trim(str_replace(["\r", "\n"], ' ', stripcslashes($descriptionMatches['description'])));
            }
        }

        return [
            'class' => $fullyQualifiedClass,
            'namespace' => $namespace ?? '',
            'path' => $relativePath,
            'description' => $description,
        ];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function runDoctrineDryRun(string $displayCommand): array
    {
        $process = new Process(['php', 'bin/console', 'doctrine:migrations:migrate', '--dry-run', '--no-interaction'], $this->projectDir);
        $process->setTimeout($this->updateConfiguration->getCommandTimeoutSeconds());

        try {
            $exitCode = $process->run();
        } catch (Throwable $exception) {
            return [1, mb_substr($exception->getMessage(), 0, self::DRY_RUN_OUTPUT_LIMIT)];
        }

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());
        if (mb_strlen($output) > self::DRY_RUN_OUTPUT_LIMIT) {
            $output = mb_substr($output, 0, self::DRY_RUN_OUTPUT_LIMIT)."\n[output truncated]";
        }

        return [$exitCode, $output];
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $warnings
     * @param array<string, mixed> $details
     * @param array<int, array{class: string, path: string, description: string, namespace?: string}> $migrations
     */
    private function writeMetadata(
        string $stagingPath,
        bool $success,
        array $checks,
        array $warnings,
        array $details,
        array $migrations,
        ?string $dryRunCommand,
        ?int $dryRunExitCode,
        string $dryRunOutput
    ): string {
        $metadataPath = $stagingPath.'/'.self::METADATA_FILE_NAME;
        $payload = [
            'created_at' => gmdate('c'),
            'success' => $success,
            'migrations' => $migrations,
            'checks' => $checks,
            'warnings' => $warnings,
            'details' => $details,
            'dry_run_command' => $dryRunCommand,
            'dry_run_exit_code' => $dryRunExitCode,
            'dry_run_output' => $dryRunOutput,
            'note' => 'This file reviews staged Doctrine migrations and stores a dry-run result. It does not create a database backup and does not execute migrations.',
        ];

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (false === file_put_contents($metadataPath, $encoded.PHP_EOL)) {
            throw new RuntimeException('Unable to write migration safety metadata: '.$metadataPath);
        }

        return $metadataPath;
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
                $errors[] = (string) ($check['message'] ?? 'Migration safety check failed.');
            }
        }

        return [] !== $errors ? $errors : ['Migration safety check failed.'];
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

    private function normalizeRelativePath(string $relativePath): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');

        if (str_contains($relativePath, "\0") || str_contains($relativePath, '../') || str_starts_with($relativePath, '../')) {
            throw new RuntimeException('Unsafe staged relative migration path detected: '.$relativePath);
        }

        return $relativePath;
    }

    private function isPathInside(string $path, string $basePath): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/').'/';
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/').'/';

        return str_starts_with($path, $basePath);
    }
}
