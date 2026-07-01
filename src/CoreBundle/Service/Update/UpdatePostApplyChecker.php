<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdatePostApplyCheckResult;
use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

final readonly class UpdatePostApplyChecker
{
    private const METADATA_FILE_NAME = 'POST-APPLY-CHECKS.json';

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
        private UpdateConfiguration $updateConfiguration,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {}

    public function check(string $stagingPath): UpdatePostApplyCheckResult
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

            $stagingMetadata = $this->readJsonFile($stagingPath.'/STAGING-INFO.json', 'staging metadata');
            $details['staging_metadata'] = $this->summarizeStagingMetadata($stagingMetadata);
            $this->addCheck($checks, 'staging_metadata', 'passed', 'Staging metadata was read successfully.');

            $applyPlan = $this->readJsonFile($stagingPath.'/APPLY-PLAN.json', 'apply plan metadata');
            $details['apply_plan'] = $this->summarizeApplyPlan($applyPlan);
            $this->addCheck($checks, 'apply_plan_metadata', 'passed', 'Apply plan metadata was read successfully.');

            $applyResult = $this->readJsonFile($stagingPath.'/APPLY-RESULT.json', 'apply result metadata');
            $details['apply_result'] = $this->summarizeApplyResult($applyResult);
            if (true !== ($applyResult['success'] ?? false)) {
                $this->addCheck($checks, 'apply_result_metadata', 'failed', 'Staged files were not applied successfully. Post-apply checks cannot continue.', [
                    'apply_result_file' => $stagingPath.'/APPLY-RESULT.json',
                ]);

                return UpdatePostApplyCheckResult::failure($this->collectFailedCheckMessages($checks), $checks, $warnings, $details);
            }

            $this->addCheck($checks, 'apply_result_metadata', 'passed', 'Apply result metadata was read successfully.');

            $applicationPath = $this->resolveApplicationPath($stagingPath, $applyPlan, $stagingMetadata);
            $details['application_path'] = $applicationPath;
            $this->addCheck($checks, 'application_path', 'passed', 'Staged application path is valid.', [
                'application_path' => $applicationPath,
            ]);

            $packageSignals = $this->detectPackageSignals($applicationPath, $applyPlan);
            $details['package_signals'] = $packageSignals;

            $actions = $this->buildRecommendedActions($packageSignals);
            $details['recommended_actions_count'] = \count($actions);

            $this->addCheck($checks, 'post_apply_actions', 'passed', 'Post-apply action recommendations were generated.', [
                'actions_count' => \count($actions),
                'action_keys' => array_map(static fn (array $action): string => $action['key'], $actions),
            ]);

            if ($packageSignals['migrations_detected']) {
                $warnings[] = 'Database migrations were detected in the staged package. Run them only after confirming that the file update is correct and a database backup exists.';
            }

            $metadataPath = $this->writePostApplyMetadata($stagingPath, $checks, $warnings, $details, $actions);

            return UpdatePostApplyCheckResult::success($stagingPath, $metadataPath, $actions, $checks, $warnings, $details);
        } catch (RuntimeException $exception) {
            return UpdatePostApplyCheckResult::failure([$exception->getMessage()], $checks, $warnings, $details);
        }
    }

    private function resolveSafeStagingPath(string $stagingPath): string
    {
        $stagingPath = rtrim(trim($stagingPath), '/');

        if ('' === $stagingPath) {
            throw new RuntimeException('Staging path is required to run post-apply checks.');
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
            throw new RuntimeException('Update '.$label.' is not valid JSON: '.$exception->getMessage(), 0, $exception);
        }

        if (!\is_array($data)) {
            throw new RuntimeException('Update '.$label.' must be a JSON object.');
        }

        return $data;
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

        if (!is_file($realApplicationPath.'/composer.json')) {
            throw new RuntimeException('Staged application path is missing composer.json.');
        }

        return $realApplicationPath;
    }

    /**
     * @param array<string, mixed> $applyPlan
     *
     * @return array{
     *     composer_files_detected: bool,
     *     frontend_files_detected: bool,
     *     migrations_detected: bool,
     *     cache_clear_recommended: bool,
     *     detected_paths: array<string, string[]>
     * }
     */
    private function detectPackageSignals(string $applicationPath, array $applyPlan): array
    {
        $detectedPaths = [
            'composer' => [],
            'frontend' => [],
            'migrations' => [],
        ];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($applicationPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $relativePath = $this->normalizeRelativePath(substr($item->getPathname(), \strlen($applicationPath) + 1));

            if ('' === $relativePath || $this->shouldSkipPath($relativePath)) {
                continue;
            }

            if ($this->isComposerPath($relativePath)) {
                $this->appendDetectedPath($detectedPaths['composer'], $relativePath);
            }

            if ($this->isFrontendPath($relativePath)) {
                $this->appendDetectedPath($detectedPaths['frontend'], $relativePath);
            }
        }

        foreach ($this->extractPlannedV210MigrationPaths($applyPlan) as $migrationPath) {
            $this->appendDetectedPath($detectedPaths['migrations'], $migrationPath);
        }

        return [
            'composer_files_detected' => [] !== $detectedPaths['composer'],
            'frontend_files_detected' => [] !== $detectedPaths['frontend'],
            'migrations_detected' => [] !== $detectedPaths['migrations'],
            'cache_clear_recommended' => true,
            'detected_paths' => $detectedPaths,
        ];
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

        $migrationFiles = $filePlan['migration_files_new'] ?? [];
        if (!\is_array($migrationFiles)) {
            return [];
        }

        $paths = [];
        foreach ($migrationFiles as $path) {
            if (!\is_string($path)) {
                continue;
            }

            $path = $this->normalizeRelativePath($path);
            if ($this->isMigrationPath($path)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @param array{
     *     composer_files_detected: bool,
     *     frontend_files_detected: bool,
     *     migrations_detected: bool,
     *     cache_clear_recommended: bool,
     *     detected_paths: array<string, string[]>
     * } $signals
     *
     * @return array<int, array{key: string, title: string, description: string, commands: string[], required: bool, severity: string}>
     */
    private function buildRecommendedActions(array $signals): array
    {
        $actions = [];

        if ($signals['composer_files_detected']) {
            $actions[] = [
                'key' => 'composer_install',
                'title' => 'Composer dependencies',
                'description' => 'Composer metadata was included in the staged package. Run Composer install from the application root before considering the update complete.',
                'commands' => [
                    $this->getComposerInstallDisplayCommand(),
                ],
                'required' => true,
                'severity' => 'warning',
            ];
        }

        if ($signals['frontend_files_detected']) {
            $actions[] = [
                'key' => 'frontend_build',
                'title' => 'Frontend assets',
                'description' => 'Frontend-related files were included in the staged package. Rebuild production assets after installing dependencies.',
                'commands' => [
                    'yarn install --frozen-lockfile',
                    'NODE_OPTIONS="--experimental-global-webcrypto --max-old-space-size=8192" yarn build',
                ],
                'required' => true,
                'severity' => 'warning',
            ];
        }

        if ($signals['migrations_detected']) {
            $actions[] = [
                'key' => 'database_migrations',
                'title' => 'Database migrations',
                'description' => 'Doctrine migration files were detected in the staged package. Confirm that a database backup exists before running migrations.',
                'commands' => [
                    'php bin/console doctrine:migrations:execute <staged-migration-class> --up --no-interaction',
                ],
                'required' => true,
                'severity' => 'danger',
            ];
        }

        if ($signals['cache_clear_recommended']) {
            $actions[] = [
                'key' => 'cache_clear',
                'title' => 'Symfony cache',
                'description' => 'Clear Symfony cache after applying update files and running any required commands.',
                'commands' => [
                    'php bin/console cache:clear',
                ],
                'required' => true,
                'severity' => 'info',
            ];
        }

        return $actions;
    }

    private function getComposerInstallDisplayCommand(): string
    {
        if ($this->updateConfiguration->isProduction()) {
            return 'composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader';
        }

        return 'composer install --no-interaction --prefer-dist';
    }

    private function isComposerPath(string $relativePath): bool
    {
        return \in_array($relativePath, ['composer.json', 'composer.lock', 'symfony.lock'], true);
    }

    private function isFrontendPath(string $relativePath): bool
    {
        if (\in_array($relativePath, ['package.json', 'yarn.lock', 'webpack.config.js', 'tailwind.config.js', 'postcss.config.js'], true)) {
            return true;
        }

        foreach (['assets/', 'public/build/'] as $prefix) {
            if (str_starts_with($relativePath, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isMigrationPath(string $relativePath): bool
    {
        return 1 === preg_match('/^src\/CoreBundle\/Migrations\/Schema\/V210\/Version[0-9]+\.php$/', $relativePath);
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

    /**
     * @param string[] $paths
     */
    private function appendDetectedPath(array &$paths, string $relativePath): void
    {
        if (\count($paths) >= 20) {
            return;
        }

        $paths[] = $relativePath;
    }

    /**
     * @param array<int, array{key: string, title: string, description: string, commands: string[], required: bool, severity: string}> $actions
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $warnings
     * @param array<string, mixed> $details
     */
    private function writePostApplyMetadata(string $stagingPath, array $checks, array $warnings, array $details, array $actions): string
    {
        $metadataPath = $stagingPath.'/'.self::METADATA_FILE_NAME;
        $metadata = [
            'created_at' => gmdate('c'),
            'actions' => $actions,
            'checks' => $checks,
            'warnings' => $warnings,
            'details' => $details,
            'note' => 'This file only reports recommended post-apply actions. It does not execute Composer, Yarn, migrations or cache commands.',
        ];

        $encoded = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (false === file_put_contents($metadataPath, $encoded.PHP_EOL)) {
            throw new RuntimeException('Unable to write post-apply checks metadata.');
        }

        return $metadataPath;
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
     * @param array<string, mixed> $applyResult
     *
     * @return array<string, mixed>
     */
    private function summarizeApplyResult(array $applyResult): array
    {
        $details = $applyResult['details'] ?? [];
        $applyPlan = \is_array($details) ? ($details['apply_plan'] ?? []) : [];

        return [
            'created_at' => $applyResult['created_at'] ?? null,
            'success' => $applyResult['success'] ?? null,
            'backup_path' => $applyResult['backup_path'] ?? null,
            'audit_path' => $applyResult['audit_path'] ?? null,
            'files_total' => \is_array($applyPlan) ? ($applyPlan['files_total'] ?? null) : null,
            'files_to_replace' => \is_array($applyPlan) ? ($applyPlan['files_to_replace'] ?? null) : null,
            'files_new' => \is_array($applyPlan) ? ($applyPlan['files_new'] ?? null) : null,
        ];
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
                $errors[] = (string) ($check['message'] ?? 'Post-apply check failed.');
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
