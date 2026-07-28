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
use const PATHINFO_FILENAME;
use const PHP_EOL;

final readonly class UpdateMigrationSafetyChecker
{
    private const string METADATA_FILE_NAME = 'MIGRATION-SAFETY-CHECKS.json';
    private const int DRY_RUN_OUTPUT_LIMIT = 20000;
    private const int BASELINE_OUTPUT_LIMIT = 30000;

    private const string SUPPORTED_MIGRATION_PREFIX = 'src/CoreBundle/Migrations/Schema/V210/';
    private const string SUPPORTED_MIGRATION_CLASS_PREFIX = 'Chamilo\CoreBundle\Migrations\Schema\V210\\';

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
        $dryRunCommand = null;
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

            $migrations = $this->collectStagedMigrations($applicationPath, $applyPlan);
            $migrationClasses = array_map(static fn (array $migration): string => $migration['class'], $migrations);
            $details['migration_count'] = \count($migrations);
            $details['migration_classes'] = $migrationClasses;

            if ([] === $migrations) {
                $this->addCheck($checks, 'migration_files', 'failed', 'No migration files were found in the staged package.');

                return UpdateMigrationSafetyCheckResult::failure(
                    $this->collectFailedCheckMessages($checks),
                    $stagingPath,
                    null,
                    $checks,
                    $warnings,
                    $details,
                    null,
                    null,
                    ''
                );
            }

            $this->addCheck($checks, 'migration_files', 'passed', 'Staged Doctrine migration files were detected.', [
                'migration_count' => \count($migrations),
                'migration_classes' => $migrationClasses,
            ]);

            $migrationTarget = $migrationClasses[\count($migrationClasses) - 1];
            $details['migration_target'] = $migrationTarget;
            $this->addCheck($checks, 'migration_target', 'passed', 'Doctrine migration target was resolved from the staged package.', [
                'migration_target' => $migrationTarget,
            ]);

            $baseline = $this->analyzeMigrationBaseline($migrationTarget, $migrationClasses);
            $details['baseline'] = $baseline;

            $baselineBlockingErrors = $baseline['blocking_errors'] ?? [];
            if (!\is_array($baselineBlockingErrors)) {
                $baselineBlockingErrors = ['Invalid migration baseline metadata.'];
            }

            if ([] !== $baselineBlockingErrors) {
                $this->addCheck($checks, 'migration_baseline', 'failed', 'Doctrine migration baseline has blocking issues for staged migrations.', [
                    'migration_target' => $migrationTarget,
                    'blocking_errors' => $baselineBlockingErrors,
                    'staged_migrations_already_executed' => $baseline['staged_migrations_already_executed'] ?? [],
                ]);

                $metadataPath = $this->writeMetadata($stagingPath, false, $checks, $warnings, $details, $migrations, null, null, '');

                return UpdateMigrationSafetyCheckResult::failure(
                    $this->collectFailedCheckMessages($checks),
                    $stagingPath,
                    $metadataPath,
                    $checks,
                    $warnings,
                    $details,
                    null,
                    null,
                    ''
                );
            }

            $historicalIssueCount = (int) ($baseline['historical_issue_count'] ?? 0);
            $targetRegistered = true === ($baseline['target_registered'] ?? false);
            if ($historicalIssueCount > 0 || !$targetRegistered) {
                $this->addCheck($checks, 'migration_baseline', 'warning', 'Doctrine reports historical migration baseline warnings, but only staged V210 migrations will be reviewed and executed explicitly.', [
                    'migration_target' => $migrationTarget,
                    'executed_unavailable_count' => $baseline['executed_unavailable_count'] ?? null,
                    'pending_before_target_count' => \is_array($baseline['pending_before_target'] ?? null) ? \count($baseline['pending_before_target']) : null,
                    'target_registered' => $targetRegistered,
                ]);
                $warnings[] = 'Doctrine reports historical migration baseline warnings. The updater will execute only staged V210 migration classes after explicit confirmation.';
            } else {
                $this->addCheck($checks, 'migration_baseline', 'passed', 'Doctrine baseline has no blocking issue for the staged V210 migrations.', [
                    'migration_target' => $migrationTarget,
                    'current' => $baseline['current'] ?? null,
                    'next' => $baseline['next'] ?? null,
                    'latest' => $baseline['latest'] ?? null,
                ]);
            }

            [$dryRunExitCode, $dryRunOutput, $dryRunCommands, $dryRunResults] = $this->runStagedMigrationDryRuns($migrations);
            $dryRunCommand = implode("\n", $dryRunCommands);
            $details['dry_run_exit_code'] = $dryRunExitCode;
            $details['staged_migration_dry_runs'] = $dryRunResults;
            $details['migration_execution_mode'] = 'explicit_execute';

            if (0 !== $dryRunExitCode) {
                $this->addCheck($checks, 'migration_dry_run', 'failed', 'Doctrine migration dry-run failed for at least one staged migration. Do not run database migrations until this is fixed.', [
                    'exit_code' => $dryRunExitCode,
                    'migration_target' => $migrationTarget,
                    'execution_mode' => 'explicit_execute',
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

            $this->addCheck($checks, 'migration_dry_run', 'passed', 'Doctrine dry-run completed successfully for each staged migration class.', [
                'command' => $dryRunCommand,
                'exit_code' => $dryRunExitCode,
                'migration_target' => $migrationTarget,
                'execution_mode' => 'explicit_execute',
                'migration_count' => \count($migrations),
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
     * @param array<string, mixed> $applyPlan
     *
     * @return array<int, array{class: string, path: string, description: string, namespace?: string}>
     */
    private function collectStagedMigrations(string $applicationPath, array $applyPlan): array
    {
        $plannedMigrationPaths = $this->extractPlannedV210MigrationPaths($applyPlan);
        $migrations = [];

        if ([] !== $plannedMigrationPaths) {
            foreach ($plannedMigrationPaths as $relativePath) {
                $absolutePath = $applicationPath.'/'.$relativePath;
                if (!is_file($absolutePath)) {
                    throw new RuntimeException('Planned staged migration file was not found: '.$relativePath);
                }

                $migrations[] = $this->parseMigrationFile($absolutePath, $relativePath);
            }

            usort($migrations, static fn (array $left, array $right): int => strcmp($left['class'], $right['class']));

            return $migrations;
        }

        $directory = $applicationPath.'/'.rtrim(self::SUPPORTED_MIGRATION_PREFIX, '/');
        if (!is_dir($directory)) {
            return [];
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
            if (!$this->isSupportedMigrationPath($relativePath)) {
                continue;
            }

            $migrations[] = $this->parseMigrationFile($item->getPathname(), $relativePath);
        }

        usort($migrations, static fn (array $left, array $right): int => strcmp($left['class'], $right['class']));

        return $migrations;
    }

    /**
     * @param array<string, mixed> $applyPlan
     *
     * @return string[]
     */
    private function extractPlannedV210MigrationPaths(array $applyPlan): array
    {
        $filePlan = $applyPlan['file_plan'] ?? [];
        if (!\is_array($filePlan)) {
            return [];
        }

        $plannedPaths = $filePlan['migration_files_new'] ?? [];
        if (!\is_array($plannedPaths)) {
            return [];
        }

        $paths = [];
        foreach ($plannedPaths as $path) {
            if (!\is_string($path)) {
                continue;
            }

            $path = $this->normalizeRelativePath($path);
            if ($this->isSupportedMigrationPath($path)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    private function isSupportedMigrationPath(string $relativePath): bool
    {
        return str_starts_with($relativePath, self::SUPPORTED_MIGRATION_PREFIX)
            && 1 === preg_match('/^src\/CoreBundle\/Migrations\/Schema\/V210\/Version[0-9]+\.php$/', $relativePath);
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

        if (!str_starts_with($fullyQualifiedClass, self::SUPPORTED_MIGRATION_CLASS_PREFIX)) {
            throw new RuntimeException('Only V210 staged migrations are supported by the update runner: '.$relativePath);
        }

        return [
            'class' => $fullyQualifiedClass,
            'namespace' => $namespace ?? '',
            'path' => $relativePath,
            'description' => $description,
        ];
    }

    /**
     * @param string[] $stagedMigrationClasses
     *
     * @return array<string, mixed>
     */
    private function analyzeMigrationBaseline(string $migrationTarget, array $stagedMigrationClasses): array
    {
        [$statusExitCode, $statusOutput] = $this->runConsoleCommand(['php', 'bin/console', 'doctrine:migrations:status']);
        [$listExitCode, $listOutput] = $this->runConsoleCommand(['php', 'bin/console', 'doctrine:migrations:list']);

        $statusRows = $this->parseConsoleTableRows($statusOutput);
        $listRows = $this->parseMigrationListRows($listOutput);

        $executedUnavailableCount = $this->readIntegerStatusValue($statusRows, 'Executed Unavailable');
        $current = $this->readStringStatusValue($statusRows, 'Current');
        $next = $this->readStringStatusValue($statusRows, 'Next');
        $latest = $this->readStringStatusValue($statusRows, 'Latest');
        $newCount = $this->readIntegerStatusValue($statusRows, 'New');
        $migrationStatusByClass = [];
        foreach ($listRows as $row) {
            $migrationStatusByClass[$row['class']] = $row['status'];
        }

        $targetRegistered = \in_array($migrationTarget, array_keys($migrationStatusByClass), true);
        $pendingBeforeTarget = $this->findPendingMigrationsBeforeTarget($listRows, $migrationTarget, $stagedMigrationClasses);
        $executedUnavailableMigrations = $this->extractExecutedUnavailableMigrations($statusOutput);
        $stagedMigrationStatuses = [];
        $stagedMigrationsAlreadyExecuted = [];

        foreach ($stagedMigrationClasses as $stagedMigrationClass) {
            $status = $migrationStatusByClass[$stagedMigrationClass] ?? 'not registered';
            $stagedMigrationStatuses[$stagedMigrationClass] = $status;

            if ('migrated' === $status) {
                $stagedMigrationsAlreadyExecuted[] = $stagedMigrationClass;
            }
        }

        $errors = [];

        if (0 !== $statusExitCode) {
            $errors[] = 'Unable to read Doctrine migration status.';
        }

        if (0 !== $listExitCode) {
            $errors[] = 'Unable to read Doctrine migration list.';
        }

        if ([] !== $stagedMigrationsAlreadyExecuted) {
            $errors[] = 'At least one staged V210 migration has already been executed.';
        }

        $historicalIssueCount = \count($pendingBeforeTarget) + \count($executedUnavailableMigrations);
        $baselineWarnings = [];

        if (!$targetRegistered) {
            $baselineWarnings[] = 'The staged V210 migration target is not listed by Doctrine, but explicit execute dry-run remains the source of truth.';
        }

        if ($historicalIssueCount > 0) {
            $baselineWarnings[] = 'Doctrine reports historical migration baseline issues outside the staged V210 update migrations.';
        }

        return [
            'clean' => [] === $errors && [] === $baselineWarnings,
            'blocking_errors' => $errors,
            'warnings' => $baselineWarnings,
            'errors' => $errors,
            'migration_target' => $migrationTarget,
            'target_registered' => $targetRegistered,
            'current' => $current,
            'next' => $next,
            'latest' => $latest,
            'new_count' => $newCount,
            'executed_unavailable_count' => $executedUnavailableCount,
            'executed_unavailable_migrations' => $executedUnavailableMigrations,
            'pending_before_target' => $pendingBeforeTarget,
            'historical_issue_count' => $historicalIssueCount,
            'staged_migration_statuses' => $stagedMigrationStatuses,
            'staged_migrations_already_executed' => $stagedMigrationsAlreadyExecuted,
            'status_exit_code' => $statusExitCode,
            'list_exit_code' => $listExitCode,
            'status_output' => $this->limitOutput($statusOutput, self::BASELINE_OUTPUT_LIMIT),
            'list_output' => $this->limitOutput($listOutput, self::BASELINE_OUTPUT_LIMIT),
        ];
    }

    /**
     * @param array<int, array{class: string, status: string}> $migrationRows
     * @param string[]                                         $stagedMigrationClasses
     *
     * @return array<int, array{class: string, status: string}>
     */
    private function findPendingMigrationsBeforeTarget(array $migrationRows, string $migrationTarget, array $stagedMigrationClasses): array
    {
        $pending = [];
        $foundTarget = false;

        foreach ($migrationRows as $row) {
            if ($migrationTarget === $row['class']) {
                $foundTarget = true;

                break;
            }

            if ('migrated' !== $row['status'] && !\in_array($row['class'], $stagedMigrationClasses, true)) {
                $pending[] = $row;
            }
        }

        if (!$foundTarget) {
            return $pending;
        }

        return $pending;
    }

    /**
     * @param array<int, array{class: string, path: string, description: string, namespace?: string}> $migrations
     *
     * @return array{0: int, 1: string, 2: string[], 3: array<int, array{class: string, command: string, exit_code: int, output: string}>}
     */
    private function runStagedMigrationDryRuns(array $migrations): array
    {
        $overallExitCode = 0;
        $combinedOutput = [];
        $commands = [];
        $results = [];

        foreach ($migrations as $migration) {
            $migrationClass = (string) $migration['class'];
            $command = ['php', 'bin/console', 'doctrine:migrations:execute', $migrationClass, '--up', '--dry-run', '--no-interaction'];
            $displayCommand = $this->formatDoctrineMigrationCommand($migrationClass, true);
            $commands[] = $displayCommand;

            [$exitCode, $output] = $this->runConsoleCommand($command);

            if (0 !== $exitCode && 0 === $overallExitCode) {
                $overallExitCode = $exitCode;
            }

            $results[] = [
                'class' => $migrationClass,
                'command' => $displayCommand,
                'exit_code' => $exitCode,
                'output' => $output,
            ];

            $combinedOutput[] = \sprintf(
                "## %s\nCommand: %s\nExit code: %d\n%s",
                $migrationClass,
                $displayCommand,
                $exitCode,
                '' !== $output ? $output : '[no output]'
            );
        }

        return [
            $overallExitCode,
            $this->limitOutput(implode("\n\n", $combinedOutput), self::DRY_RUN_OUTPUT_LIMIT),
            $commands,
            $results,
        ];
    }

    /**
     * @param string[] $command
     *
     * @return array{0: int, 1: string}
     */
    private function runConsoleCommand(array $command): array
    {
        $process = new Process($command, $this->projectDir);
        $process->setTimeout($this->updateConfiguration->getCommandTimeoutSeconds());

        try {
            $exitCode = $process->run();
        } catch (Throwable $exception) {
            return [1, mb_substr($exception->getMessage(), 0, self::DRY_RUN_OUTPUT_LIMIT)];
        }

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        return [$exitCode, $this->limitOutput($output, self::DRY_RUN_OUTPUT_LIMIT)];
    }

    private function formatDoctrineMigrationCommand(string $migrationClass, bool $dryRun): string
    {
        $command = 'php bin/console doctrine:migrations:execute '.escapeshellarg($migrationClass).' --up';

        if ($dryRun) {
            $command .= ' --dry-run';
        }

        return $command.' --no-interaction';
    }

    /**
     * @return array<int, string[]>
     */
    private function parseConsoleTableRows(string $output): array
    {
        $rows = [];

        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            $line = trim($line);

            if (!str_starts_with($line, '|')) {
                continue;
            }

            $cells = array_map(
                static fn (string $cell): string => trim($cell),
                explode('|', trim($line, '|'))
            );

            if (\count($cells) < 2 || str_starts_with($cells[0], '---')) {
                continue;
            }

            $rows[] = $cells;
        }

        return $rows;
    }

    /**
     * @param array<int, string[]> $rows
     */
    private function readStringStatusValue(array $rows, string $label): ?string
    {
        foreach ($rows as $cells) {
            for ($index = 0; $index < \count($cells) - 1; $index++) {
                if ($label === $cells[$index]) {
                    $value = trim($cells[$index + 1]);

                    return '' !== $value ? $value : null;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, string[]> $rows
     */
    private function readIntegerStatusValue(array $rows, string $label): ?int
    {
        $value = $this->readStringStatusValue($rows, $label);

        if (null === $value || !preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return array<int, array{class: string, status: string}>
     */
    private function parseMigrationListRows(string $output): array
    {
        $rows = [];

        foreach ($this->parseConsoleTableRows($output) as $cells) {
            $class = $cells[0] ?? '';
            $status = $cells[1] ?? '';

            if (!str_starts_with($class, 'Chamilo\CoreBundle\Migrations\Schema\\')) {
                continue;
            }

            $rows[] = [
                'class' => $class,
                'status' => mb_strtolower(trim($status)),
            ];
        }

        return $rows;
    }

    /**
     * @return string[]
     */
    private function extractExecutedUnavailableMigrations(string $statusOutput): array
    {
        $migrations = [];

        foreach (preg_split('/\R/', $statusOutput) ?: [] as $line) {
            if (!str_contains($line, '>>')) {
                continue;
            }

            if (preg_match('/Chamilo\\\CoreBundle\\\Migrations\\\Schema\\\V(?:200|210)\\\Version[0-9]+/', $line, $matches)) {
                $migrations[] = $matches[0];
            }
        }

        return array_values(array_unique($migrations));
    }

    private function limitOutput(string $output, int $limit): string
    {
        if (mb_strlen($output) <= $limit) {
            return $output;
        }

        return mb_substr($output, 0, $limit)."\n[output truncated]";
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[]                                                                                        $warnings
     * @param array<string, mixed>                                                                            $details
     * @param array<int, array{class: string, path: string, description: string, namespace?: string}>         $migrations
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
            'migration_target' => $details['migration_target'] ?? null,
            'baseline' => $details['baseline'] ?? null,
            'migrations' => $migrations,
            'checks' => $checks,
            'warnings' => $warnings,
            'details' => $details,
            'dry_run_command' => $dryRunCommand,
            'dry_run_exit_code' => $dryRunExitCode,
            'dry_run_output' => $dryRunOutput,
            'note' => 'This file reviews staged Doctrine migrations and stores explicit per-migration dry-run results. It does not create a database backup and does not execute migrations.',
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
