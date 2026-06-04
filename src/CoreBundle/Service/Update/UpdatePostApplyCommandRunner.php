<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdatePostApplyRunResult;
use JsonException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;
use Throwable;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

final readonly class UpdatePostApplyCommandRunner
{
    private const SUPPORTED_MIGRATION_CLASS_PREFIX = 'Chamilo\\CoreBundle\\Migrations\\Schema\\V210\\';
    private const METADATA_FILE_NAME = 'POST-APPLY-RUN-RESULT.json';

    private const ADVANCED_ACTION_KEYS = [
        'composer_install',
        'yarn_install',
        'yarn_build',
        'doctrine_migrations',
    ];

    public function __construct(
        private UpdateOperationLogger $operationLogger,
        private UpdateConfiguration $updateConfiguration,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {}

    /**
     * @param string[] $requestedActions
     */
    public function run(
        string $stagingPath,
        array $requestedActions,
        bool $confirmed,
        ?string $operationId = null,
        bool $confirmedAdvanced = false,
        bool $confirmedDatabaseBackup = false,
        bool $confirmedDatabaseMigrations = false
    ): UpdatePostApplyRunResult {
        $checks = [];
        $warnings = [];
        $actions = [];
        $details = [
            'project_dir' => $this->projectDir,
            'requested_staging_path' => $stagingPath,
            'requested_actions' => array_values($requestedActions),
            'confirmed' => $confirmed,
            'confirmed_advanced' => $confirmedAdvanced,
            'confirmed_database_backup' => $confirmedDatabaseBackup,
            'confirmed_database_migrations' => $confirmedDatabaseMigrations,
            'environment' => $this->updateConfiguration->isProduction() ? 'prod' : 'dev',
        ];
        $metadataPath = null;

        try {
            $operationId = $this->operationLogger->create($operationId);
            $details['operation_id'] = $operationId;
            $this->logOperation($operationId, 'info', 'post_apply_start', 'Starting post-apply actions.');

            if (!$confirmed) {
                throw new RuntimeException('Post-apply command execution requires explicit confirmation.');
            }

            $stagingPath = $this->resolveSafeStagingPath($stagingPath);
            $details['staging_path'] = $stagingPath;
            $this->addCheck($checks, 'staging_path', 'passed', 'Staging directory is inside the Chamilo update staging directory.', [
                'staging_path' => $stagingPath,
            ]);
            $this->logOperation($operationId, 'success', 'staging_path', 'Staging directory was validated.');

            $postApplyChecks = $this->readJsonFile($stagingPath.'/POST-APPLY-CHECKS.json', 'post-apply checks');
            $recommendedActionKeys = $this->extractRecommendedActionKeys($postApplyChecks);
            $details['recommended_action_keys'] = $recommendedActionKeys;
            $this->addCheck($checks, 'post_apply_metadata', 'passed', 'Post-apply recommendation metadata was read successfully.', [
                'recommended_action_keys' => $recommendedActionKeys,
            ]);
            $this->logOperation($operationId, 'success', 'post_apply_metadata', 'Post-apply recommendation metadata was read successfully.');

            $selectedActions = $this->resolveRequestedActions($requestedActions, $recommendedActionKeys);
            $selectedActionKeys = array_keys($selectedActions);
            $details['selected_action_keys'] = $selectedActionKeys;
            $this->addCheck($checks, 'selected_actions', 'passed', 'Post-apply actions were validated against the recommendation report.', [
                'selected_action_keys' => $selectedActionKeys,
            ]);
            $this->logOperation($operationId, 'info', 'selected_actions', 'Post-apply actions were selected.', [
                'actions' => $selectedActionKeys,
            ]);

            if ($this->containsAdvancedActions($selectedActionKeys)) {
                if (!$confirmedAdvanced) {
                    throw new RuntimeException('Advanced post-apply actions require explicit advanced confirmation.');
                }

                $this->addCheck($checks, 'advanced_actions_confirmation', 'passed', 'Advanced post-apply actions were explicitly confirmed.', [
                    'advanced_action_keys' => $this->filterAdvancedActionKeys($selectedActionKeys),
                ]);
                $this->logOperation($operationId, 'warning', 'advanced_actions_confirmation', 'Advanced post-apply actions were explicitly confirmed.');
            }

            if (isset($selectedActions['doctrine_migrations'])) {
                $migrationClasses = $this->validateDatabaseMigrationReview(
                    $stagingPath,
                    $confirmedDatabaseBackup,
                    $confirmedDatabaseMigrations,
                    $checks,
                    $warnings,
                    $details,
                    $operationId
                );

                $migrationCommands = [];
                $displayCommands = [];
                foreach ($migrationClasses as $migrationClass) {
                    $migrationCommands[] = [
                        'php',
                        'bin/console',
                        'doctrine:migrations:execute',
                        $migrationClass,
                        '--up',
                        '--no-interaction',
                    ];
                    $displayCommands[] = 'php bin/console doctrine:migrations:execute '
                        .escapeshellarg($migrationClass)
                        .' --up --no-interaction';
                }

                $selectedActions['doctrine_migrations']['commands'] = $migrationCommands;
                $selectedActions['doctrine_migrations']['command'] = $migrationCommands[0] ?? [];
                $selectedActions['doctrine_migrations']['display_command'] = implode("\n", $displayCommands);
            }

            $this->validateExecutableActions($selectedActions, $checks, $warnings, $details, $operationId);

            $lockPath = $this->acquireLock();
            $details['lock_path'] = $lockPath;
            $this->addCheck($checks, 'update_lock', 'passed', 'Update lock was acquired.', [
                'lock_path' => $lockPath,
            ]);
            $this->logOperation($operationId, 'success', 'update_lock', 'Update lock was acquired.');

            try {
                foreach ($selectedActions as $key => $definition) {
                    $actions[] = $this->runAction($key, $definition, $operationId);
                }

                $this->addCheck($checks, 'post_apply_commands', 'passed', 'Selected post-apply commands completed successfully.', [
                    'actions_count' => \count($actions),
                ]);
                $this->logOperation($operationId, 'success', 'post_apply_commands', 'Selected post-apply commands completed successfully.');

                $metadataPath = $this->writeRunMetadata($stagingPath, true, $checks, $actions, $warnings, $details);
                $this->addCheck($checks, 'post_apply_run_metadata', 'passed', 'Post-apply command run metadata was written.', [
                    'metadata_file' => $metadataPath,
                ]);
                $metadataPath = $this->writeRunMetadata($stagingPath, true, $checks, $actions, $warnings, $details);
                $this->logOperation($operationId, 'success', 'done', 'Post-apply actions completed successfully.');

                return UpdatePostApplyRunResult::success($stagingPath, $metadataPath, $operationId, $checks, $actions, $warnings, $details);
            } finally {
                $this->releaseLock($lockPath);
            }
        } catch (Throwable $exception) {
            $errors = [$exception->getMessage()];
            $details['exception'] = $exception::class;

            if (isset($operationId) && \is_string($operationId) && '' !== $operationId) {
                $this->logOperation($operationId, 'error', 'failed', $exception->getMessage());
            }

            if (isset($stagingPath) && \is_string($stagingPath) && is_dir($stagingPath)) {
                try {
                    $metadataPath = $this->writeRunMetadata($stagingPath, false, $checks, $actions, $warnings, $details, $errors);
                } catch (Throwable) {
                }
            }

            return UpdatePostApplyRunResult::failure($errors, $details['staging_path'] ?? null, $metadataPath, $operationId, $checks, $actions, $warnings, $details);
        }
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return array{key: string, title: string, command: string, status: string, exitCode?: int|null, durationSeconds?: float, advanced?: bool}
     */
    private function runAction(string $key, array $definition, string $operationId): array
    {
        $displayCommand = (string) $definition['display_command'];
        $title = (string) $definition['title'];
        $commands = $definition['commands'] ?? null;
        $command = $definition['command'] ?? [];

        if (\is_array($commands)) {
            $commandList = $commands;
        } else {
            $commandList = [$command];
        }

        if ([] === $commandList) {
            throw new RuntimeException('Invalid update post-apply command definition: '.$key);
        }

        foreach ($commandList as $commandItem) {
            if (!\is_array($commandItem) || [] === $commandItem) {
                throw new RuntimeException('Invalid update post-apply command definition: '.$key);
            }
        }

        $this->logOperation($operationId, 'info', $key, 'Running post-apply action: '.$title, [
            'command' => $displayCommand,
        ]);

        $environment = $definition['environment'] ?? null;
        if ('composer_install' === $key) {
            $environment = array_merge(
                \is_array($environment) ? $environment : [],
                $this->getComposerEnvironment()
            );
        }

        $startedAt = microtime(true);
        $outputBuffer = '';
        $exitCode = 0;

        foreach ($commandList as $index => $commandItem) {
            $process = new Process($commandItem, $this->projectDir);
            $process->setTimeout($this->updateConfiguration->getCommandTimeoutSeconds());

            if (\is_array($environment) && [] !== $environment) {
                $process->setEnv($environment);
            }

            if (\count($commandList) > 1) {
                $this->logOperation($operationId, 'info', $key, sprintf('Running command %d of %d for %s.', $index + 1, \count($commandList), $title));
            }

            $exitCode = $process->run(function (string $type, string $buffer) use ($operationId, $key, &$outputBuffer): void {
                $outputBuffer .= $buffer;

                foreach ($this->splitProcessOutput($buffer) as $line) {
                    $this->logOperation($operationId, Process::ERR === $type ? 'warning' : 'info', $key.'_output', $line);
                }
            });

            if (0 !== $exitCode) {
                break;
            }
        }

        $duration = round(microtime(true) - $startedAt, 3);

        if (0 !== $exitCode) {
            $this->logOperation($operationId, 'error', $key, 'Post-apply action failed: '.$title, [
                'exit_code' => $exitCode,
            ]);

            throw new RuntimeException(sprintf(
                'Post-apply action "%s" failed with exit code %d.',
                $title,
                $exitCode
            ));
        }

        $this->logOperation($operationId, 'success', $key, 'Post-apply action completed: '.$title, [
            'duration_seconds' => $duration,
        ]);

        return [
            'key' => $key,
            'title' => $title,
            'command' => $displayCommand,
            'status' => 'passed',
            'exitCode' => $exitCode,
            'durationSeconds' => $duration,
            'advanced' => $this->isAdvancedActionKey($key),
        ];
    }

    /**
     * @return string[]
     */
    private function splitProcessOutput(string $buffer): array
    {
        $lines = preg_split('/\R/', $buffer) ?: [];
        $cleanLines = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ('' === $line) {
                continue;
            }

            $cleanLines[] = mb_substr($line, 0, 800);
        }

        return $cleanLines;
    }

    private function resolveSafeStagingPath(string $stagingPath): string
    {
        $stagingPath = rtrim(trim($stagingPath), '/');

        if ('' === $stagingPath) {
            throw new RuntimeException('Staging path is required to run post-apply actions.');
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
     *
     * @return string[]
     */
    private function extractRecommendedActionKeys(array $postApplyChecks): array
    {
        $actions = $postApplyChecks['actions'] ?? [];

        if (!\is_array($actions)) {
            throw new RuntimeException('Post-apply checks metadata does not contain a valid actions list.');
        }

        $keys = [];

        foreach ($actions as $action) {
            if (!\is_array($action)) {
                continue;
            }

            $key = $action['key'] ?? null;
            if (\is_string($key) && '' !== trim($key)) {
                $keys[] = trim($key);
            }
        }

        if ([] === $keys) {
            throw new RuntimeException('There are no recommended post-apply actions to run.');
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param string[] $requestedActions
     * @param string[] $recommendedActionKeys
     *
     * @return array<string, array<string, mixed>>
     */
    private function resolveRequestedActions(array $requestedActions, array $recommendedActionKeys): array
    {
        $requestedActions = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => \is_string($value) ? trim($value) : '',
            $requestedActions,
        ))));

        $actionDefinitions = $this->getActionDefinitions();
        $allowedExecutableActions = [];

        foreach ($actionDefinitions as $key => $definition) {
            $sourceAction = (string) $definition['source_action'];
            if (\in_array($sourceAction, $recommendedActionKeys, true)) {
                $allowedExecutableActions[$key] = $definition;
            }
        }

        if ([] === $requestedActions || \in_array('__all__', $requestedActions, true)) {
            if ([] === $allowedExecutableActions) {
                throw new RuntimeException('There are no allowed post-apply actions to run.');
            }

            return $allowedExecutableActions;
        }

        $selectedActions = [];

        foreach ($requestedActions as $key) {
            if (!isset($allowedExecutableActions[$key])) {
                throw new RuntimeException('Post-apply action is not allowed for this update: '.$key);
            }

            $selectedActions[$key] = $allowedExecutableActions[$key];
        }

        return $selectedActions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getActionDefinitions(): array
    {
        $composerCommand = ['composer', 'install', '--no-interaction', '--prefer-dist'];
        $composerDisplayCommand = 'composer install --no-interaction --prefer-dist';

        if ($this->updateConfiguration->isProduction()) {
            $composerCommand = ['composer', 'install', '--no-dev', '--no-interaction', '--prefer-dist', '--optimize-autoloader'];
            $composerDisplayCommand = 'composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader';
        }

        return [
            'composer_install' => [
                'title' => 'Composer dependencies',
                'command' => $composerCommand,
                'display_command' => $composerDisplayCommand,
                'source_action' => 'composer_install',
                'advanced' => true,
            ],
            'yarn_install' => [
                'title' => 'Frontend dependencies',
                'command' => ['yarn', 'install', '--frozen-lockfile'],
                'display_command' => 'yarn install --frozen-lockfile',
                'source_action' => 'frontend_build',
                'advanced' => true,
            ],
            'yarn_build' => [
                'title' => 'Frontend assets',
                'command' => ['yarn', 'build'],
                'display_command' => 'NODE_OPTIONS="--max-old-space-size=8192" yarn build',
                'source_action' => 'frontend_build',
                'environment' => [
                    'NODE_OPTIONS' => '--max-old-space-size=8192',
                ],
                'advanced' => true,
            ],
            'doctrine_migrations' => [
                'title' => 'Database migrations',
                'command' => ['php', 'bin/console', 'doctrine:migrations:execute', '--help'],
                'display_command' => 'php bin/console doctrine:migrations:execute <staged-migration-class> --up --no-interaction',
                'source_action' => 'database_migrations',
                'advanced' => true,
            ],
            'cache_clear' => [
                'title' => 'Symfony cache',
                'command' => ['php', 'bin/console', 'cache:clear'],
                'display_command' => 'php bin/console cache:clear',
                'source_action' => 'cache_clear',
                'advanced' => false,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getComposerEnvironment(): array
    {
        $baseDirectory = $this->projectDir.'/var/update/composer';
        $homeDirectory = $baseDirectory.'/home';
        $cacheDirectory = $baseDirectory.'/cache';

        $this->ensureDirectory($homeDirectory);
        $this->ensureDirectory($cacheDirectory);

        return [
            'COMPOSER_HOME' => $homeDirectory,
            'COMPOSER_CACHE_DIR' => $cacheDirectory,
        ];
    }


    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $warnings
     * @param array<string, mixed> $details
     *
     * @return string[]
     */
    private function validateDatabaseMigrationReview(
        string $stagingPath,
        bool $confirmedDatabaseBackup,
        bool $confirmedDatabaseMigrations,
        array &$checks,
        array &$warnings,
        array &$details,
        string $operationId
    ): array {
        $migrationSafetyPath = $stagingPath.'/MIGRATION-SAFETY-CHECKS.json';

        if (!is_file($migrationSafetyPath) || !is_readable($migrationSafetyPath)) {
            throw new RuntimeException('Database migrations require a migration safety review before execution.');
        }

        $migrationSafety = $this->readJsonFile($migrationSafetyPath, 'migration safety checks');

        if (true !== ($migrationSafety['success'] ?? false)) {
            throw new RuntimeException('Database migrations cannot run because the migration safety review did not pass.');
        }

        $migrations = $migrationSafety['migrations'] ?? [];
        if (!\is_array($migrations) || [] === $migrations) {
            throw new RuntimeException('Database migrations cannot run because the migration safety review did not list staged migrations.');
        }

        $migrationClasses = [];
        foreach ($migrations as $migration) {
            if (!\is_array($migration) || !\is_string($migration['class'] ?? null) || '' === trim($migration['class'])) {
                throw new RuntimeException('Database migrations cannot run because the migration safety review contains an invalid migration entry.');
            }

            $migrationClass = trim($migration['class']);
            if (!str_starts_with($migrationClass, self::SUPPORTED_MIGRATION_CLASS_PREFIX)) {
                throw new RuntimeException('Only staged V210 migrations can be executed by the update runner.');
            }

            $migrationClasses[] = $migrationClass;
        }

        $baseline = $migrationSafety['baseline'] ?? $migrationSafety['details']['baseline'] ?? null;
        if (\is_array($baseline)) {
            $blockingErrors = $baseline['blocking_errors'] ?? [];
            if (\is_array($blockingErrors) && [] !== $blockingErrors) {
                throw new RuntimeException('Database migrations cannot run because the staged migration safety review reported blocking errors.');
            }

            if (true !== ($baseline['clean'] ?? false)) {
                $warnings[] = 'Doctrine reports migration baseline warnings. Only staged V210 migration classes will be executed explicitly.';
            }
        }

        if (!$confirmedDatabaseBackup) {
            throw new RuntimeException('Database migrations require confirmation that a database backup exists.');
        }

        if (!$confirmedDatabaseMigrations) {
            throw new RuntimeException('Database migrations require the confirmation text "RUN DATABASE MIGRATIONS".');
        }

        $details['migration_safety'] = [
            'metadata_file' => $migrationSafetyPath,
            'migration_count' => \count($migrationClasses),
            'migration_classes' => $migrationClasses,
            'execution_mode' => $migrationSafety['details']['migration_execution_mode'] ?? 'explicit_execute',
            'dry_run_exit_code' => $migrationSafety['dry_run_exit_code'] ?? null,
        ];

        $this->addCheck($checks, 'database_migration_safety', 'passed', 'Database migration safety review was completed before execution.', [
            'metadata_file' => $migrationSafetyPath,
            'migration_count' => \count($migrationClasses),
            'migration_classes' => $migrationClasses,
            'execution_mode' => 'explicit_execute',
        ]);
        $this->addCheck($checks, 'database_backup_confirmation', 'passed', 'Database backup existence was explicitly confirmed.');
        $this->logOperation($operationId, 'warning', 'database_migration_safety', 'Database migration safety review and backup confirmation were provided.', [
            'metadata_file' => $migrationSafetyPath,
            'migration_count' => \count($migrationClasses),
            'migration_classes' => $migrationClasses,
            'execution_mode' => 'explicit_execute',
        ]);

        $warnings[] = 'Database migrations were executed after an explicit database backup confirmation. The updater did not create the database backup.';

        return $migrationClasses;
    }

    /**
     * @param array<string, array<string, mixed>> $selectedActions
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $warnings
     * @param array<string, mixed> $details
     */
    private function validateExecutableActions(array $selectedActions, array &$checks, array &$warnings, array &$details, string $operationId): void
    {
        $selectedActionKeys = array_keys($selectedActions);

        if (isset($selectedActions['cache_clear'])) {
            $this->assertWritablePathForAction($this->projectDir.'/var/cache', $this->projectDir.'/var', 'cache_clear');
        }

        if (isset($selectedActions['composer_install'])) {
            $this->assertWritablePathForAction($this->projectDir.'/vendor', $this->projectDir, 'composer_install');
            $this->getComposerEnvironment();
        }

        if (isset($selectedActions['yarn_install'])) {
            $this->assertWritablePathForAction($this->projectDir.'/node_modules', $this->projectDir, 'yarn_install');
        }

        if (isset($selectedActions['yarn_build'])) {
            $this->assertWritablePathForAction($this->projectDir.'/public/build', $this->projectDir.'/public', 'yarn_build');
        }

        if (isset($selectedActions['doctrine_migrations']) && !is_file($this->projectDir.'/bin/console')) {
            throw new RuntimeException('Unable to run database migrations because bin/console was not found.');
        }

        $this->addCheck($checks, 'post_apply_command_permissions', 'passed', 'Selected post-apply commands have the required writable paths.', [
            'selected_action_keys' => $selectedActionKeys,
        ]);
        $this->logOperation($operationId, 'success', 'post_apply_command_permissions', 'Post-apply command writable paths were validated.');

        if ($this->containsAdvancedActions($selectedActionKeys)) {
            $warning = 'Advanced post-apply actions can modify dependencies, generated assets or the database. Review backups before continuing.';
            $warnings[] = $warning;
            $this->logOperation($operationId, 'warning', 'advanced_actions', $warning);
        }

        $details['command_validation'] = [
            'selected_action_keys' => $selectedActionKeys,
            'advanced_action_keys' => $this->filterAdvancedActionKeys($selectedActionKeys),
        ];
    }

    private function assertWritablePathForAction(string $path, string $parentDirectory, string $actionKey): void
    {
        if (is_dir($path) || is_file($path)) {
            if (!is_writable($path)) {
                throw new RuntimeException(sprintf('Path required by post-apply action "%s" is not writable: %s', $actionKey, $path));
            }

            return;
        }

        if (!is_dir($parentDirectory) || !is_writable($parentDirectory)) {
            throw new RuntimeException(sprintf('Parent directory required by post-apply action "%s" is not writable: %s', $actionKey, $parentDirectory));
        }
    }

    private function containsAdvancedActions(array $actionKeys): bool
    {
        return [] !== $this->filterAdvancedActionKeys($actionKeys);
    }

    /**
     * @param string[] $actionKeys
     *
     * @return string[]
     */
    private function filterAdvancedActionKeys(array $actionKeys): array
    {
        return array_values(array_filter(
            $actionKeys,
            fn (string $key): bool => $this->isAdvancedActionKey($key)
        ));
    }

    private function isAdvancedActionKey(string $key): bool
    {
        return \in_array($key, self::ADVANCED_ACTION_KEYS, true);
    }

    private function acquireLock(): string
    {
        $lockPath = $this->projectDir.'/var/update/update.lock';
        $lockDirectory = \dirname($lockPath);
        $this->ensureDirectory($lockDirectory);

        if (is_file($lockPath)) {
            throw new RuntimeException('Another update operation appears to be running. Remove var/update/update.lock only if no update is active.');
        }

        $content = json_encode([
            'created_at' => gmdate('c'),
            'pid' => getmypid(),
            'step' => 'post_apply_actions',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (false === file_put_contents($lockPath, $content ?: '{}', LOCK_EX)) {
            throw new RuntimeException('Unable to create update lock: '.$lockPath);
        }

        return $lockPath;
    }

    private function releaseLock(string $lockPath): void
    {
        if (is_file($lockPath)) {
            @unlink($lockPath);
        }
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

    private function isPathInside(string $path, string $basePath): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/').'/';
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/').'/';

        return str_starts_with($path, $basePath);
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
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

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param array<int, array{key: string, title: string, command: string, status: string, exitCode?: int|null, durationSeconds?: float, advanced?: bool}> $actions
     * @param string[] $warnings
     * @param array<string, mixed> $details
     * @param string[] $errors
     */
    private function writeRunMetadata(
        string $stagingPath,
        bool $success,
        array $checks,
        array $actions,
        array $warnings,
        array $details,
        array $errors = []
    ): string {
        $metadataPath = $stagingPath.'/'.self::METADATA_FILE_NAME;

        $payload = [
            'created_at' => gmdate('c'),
            'success' => $success,
            'errors' => $errors,
            'warnings' => $warnings,
            'checks' => $checks,
            'actions' => $actions,
            'details' => $details,
            'note' => 'This file records controlled post-apply commands executed after file application.',
        ];

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (false === file_put_contents($metadataPath, $encoded)) {
            throw new RuntimeException('Unable to write post-apply command run metadata: '.$metadataPath);
        }

        return $metadataPath;
    }

    private function logOperation(?string $operationId, string $level, string $step, string $message, array $details = []): void
    {
        if (null === $operationId || '' === trim($operationId)) {
            return;
        }

        try {
            $this->operationLogger->append($operationId, $level, $step, $message, $details);
        } catch (Throwable) {
            // Operation logging must never interrupt post-apply command execution.
        }
    }
}
