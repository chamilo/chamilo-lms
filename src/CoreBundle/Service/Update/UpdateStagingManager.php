<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\Dto\UpdateStagingResult;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;
use ZipArchive;

final readonly class UpdateStagingManager
{
    public function __construct(
        private UpdateArchiveInspector $archiveInspector,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {}

    public function stage(UpdateManifest $manifest, string $packagePath): UpdateStagingResult
    {
        $checks = [];
        $warnings = [];
        $details = [
            'package_path' => $packagePath,
            'target_version' => $manifest->getVersion(),
        ];

        try {
            $archiveDetails = $this->archiveInspector->inspect($packagePath);
            $details['archive'] = $archiveDetails;
            $this->addCheck($checks, 'archive_safety', 'passed', 'Update package archive safety checks passed.', $archiveDetails);

            $stagingDirectory = $this->createStagingDirectory($manifest);
            $details['staging_directory'] = $stagingDirectory;

            $this->extractZip($packagePath, $stagingDirectory);
            $this->addCheck($checks, 'archive_extraction', 'passed', 'Update package was extracted to staging.', [
                'staging_directory' => $stagingDirectory,
            ]);

            $applicationPath = $this->resolveApplicationPath($stagingDirectory);
            $details['application_path'] = $applicationPath;

            $this->validateChamiloPackageStructure($applicationPath, $checks);

            $this->writeStagingMetadata($stagingDirectory, $manifest, $packagePath, $applicationPath, $archiveDetails);
            $this->addCheck($checks, 'staging_metadata', 'passed', 'Staging metadata was written.', [
                'metadata_file' => $stagingDirectory.'/STAGING-INFO.json',
            ]);

            return UpdateStagingResult::success($stagingDirectory, $applicationPath, $checks, $warnings, $details);
        } catch (Throwable $exception) {
            $errors = [$exception->getMessage()];
            $details['exception'] = $exception::class;

            if (isset($stagingDirectory) && is_dir($stagingDirectory)) {
                $this->removeDirectory($stagingDirectory);
                $details['staging_directory_removed'] = $stagingDirectory;
            }

            return UpdateStagingResult::failure($errors, $checks, $warnings, $details);
        }
    }

    private function createStagingDirectory(UpdateManifest $manifest): string
    {
        $baseDirectory = $this->projectDir.'/var/update/staging';
        $this->ensureDirectory($baseDirectory);

        $version = preg_replace('/[^A-Za-z0-9._-]/', '_', $manifest->getVersion()) ?: 'unknown';
        $directory = $baseDirectory.'/'.$version.'-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(4));

        $this->ensureDirectory($directory);

        return $directory;
    }

    private function extractZip(string $packagePath, string $stagingDirectory): void
    {
        $zip = new ZipArchive();
        $openResult = $zip->open($packagePath);

        if (true !== $openResult) {
            throw new RuntimeException('Update package is not a valid ZIP archive.');
        }

        try {
            if (!$zip->extractTo($stagingDirectory)) {
                throw new RuntimeException('Unable to extract update package to staging directory.');
            }
        } finally {
            $zip->close();
        }
    }

    private function resolveApplicationPath(string $stagingDirectory): string
    {
        if (is_file($stagingDirectory.'/composer.json')) {
            return $stagingDirectory;
        }

        $entries = array_values(array_filter(scandir($stagingDirectory) ?: [], static fn (string $entry): bool => !\in_array($entry, ['.', '..', 'STAGING-INFO.json'], true)));

        if (1 === \count($entries)) {
            $candidate = $stagingDirectory.'/'.$entries[0];

            if (is_dir($candidate) && is_file($candidate.'/composer.json')) {
                return $candidate;
            }
        }

        foreach ($entries as $entry) {
            $candidate = $stagingDirectory.'/'.$entry;

            if (is_dir($candidate) && is_file($candidate.'/composer.json')) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to locate Chamilo application root in staged package.');
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     */
    private function validateChamiloPackageStructure(string $applicationPath, array &$checks): void
    {
        $requiredEntries = [
            'composer.json' => 'file',
            'src' => 'dir',
            'public' => 'dir',
        ];

        foreach ($requiredEntries as $entry => $type) {
            $path = $applicationPath.'/'.$entry;
            $valid = 'file' === $type ? is_file($path) : is_dir($path);

            if (!$valid) {
                throw new RuntimeException('Staged package is missing required Chamilo entry: '.$entry);
            }
        }

        $this->addCheck($checks, 'package_structure', 'passed', 'Staged package contains the expected Chamilo structure.', [
            'application_path' => $applicationPath,
            'required_entries' => array_keys($requiredEntries),
        ]);

        if (!is_file($applicationPath.'/composer.lock')) {
            $this->addCheck($checks, 'composer_lock', 'warning', 'Staged package does not contain composer.lock.', [
                'application_path' => $applicationPath,
            ]);
        } else {
            $this->addCheck($checks, 'composer_lock', 'passed', 'Staged package contains composer.lock.');
        }

        if (!is_file($applicationPath.'/yarn.lock')) {
            $this->addCheck($checks, 'yarn_lock', 'warning', 'Staged package does not contain yarn.lock.', [
                'application_path' => $applicationPath,
            ]);
        } else {
            $this->addCheck($checks, 'yarn_lock', 'passed', 'Staged package contains yarn.lock.');
        }
    }

    /**
     * @param array<string, mixed> $archiveDetails
     */
    private function writeStagingMetadata(
        string $stagingDirectory,
        UpdateManifest $manifest,
        string $packagePath,
        string $applicationPath,
        array $archiveDetails
    ): void {
        $metadata = [
            'created_at' => gmdate('c'),
            'manifest' => [
                'channel' => $manifest->getChannel(),
                'version' => $manifest->getVersion(),
                'released_at' => $manifest->getReleasedAt(),
                'package_url' => $manifest->getPackageUrl(),
                'package_sha256' => $manifest->getPackageSha256(),
                'signature_type' => $manifest->getSignatureType(),
                'signature_url' => $manifest->getSignatureUrl(),
                'requirements' => $manifest->getRequirements(),
            ],
            'package_path' => $packagePath,
            'application_path' => $applicationPath,
            'archive' => $archiveDetails,
        ];

        $encoded = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!\is_string($encoded)) {
            throw new RuntimeException('Unable to encode staging metadata.');
        }

        if (false === file_put_contents($stagingDirectory.'/STAGING-INFO.json', $encoded.PHP_EOL)) {
            throw new RuntimeException('Unable to write staging metadata.');
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

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory.'/'.$item;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
