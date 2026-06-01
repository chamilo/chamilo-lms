<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\Dto\UpdatePreflightResult;
use Composer\InstalledVersions;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

final readonly class UpdatePreflightChecker
{
    private const MINIMUM_FREE_SPACE_BYTES = 209715200;
    private const PACKAGE_SPACE_MULTIPLIER = 3;

    public function __construct(
        private KernelInterface $kernel,
    ) {}

    public function check(UpdateManifest $manifest, ?string $packagePath = null): UpdatePreflightResult
    {
        $checks = [];
        $errors = [];
        $warnings = [];
        $projectDir = $this->getProjectDir();
        $updateDirectory = $projectDir.'/var/update/downloads';
        $details = [
            'project_dir' => $projectDir,
            'update_directory' => $updateDirectory,
            'installed_version' => $this->getInstalledVersion(),
            'target_version' => $manifest->getVersion(),
            'php_version' => PHP_VERSION,
        ];

        $this->checkUpdateDirectory($updateDirectory, $projectDir, $checks, $errors, $warnings);
        $this->checkDiskSpace($updateDirectory, $projectDir, $packagePath, $checks, $errors, $warnings);
        $this->checkPhpRequirement($manifest, $checks, $errors, $warnings);
        $this->checkPackagePath($packagePath, $checks, $errors, $warnings);
        $this->checkVersionDirection($manifest, $checks, $errors, $warnings);
        $this->checkGitWorkingTree($projectDir, $checks, $warnings);
        $this->checkProjectMetadata($projectDir, $checks, $warnings);

        return UpdatePreflightResult::create($checks, $errors, $warnings, $details);
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $errors
     * @param string[] $warnings
     */
    private function checkUpdateDirectory(
        string $updateDirectory,
        string $projectDir,
        array &$checks,
        array &$errors,
        array &$warnings
    ): void {
        if (is_dir($updateDirectory)) {
            if (is_writable($updateDirectory)) {
                $this->addCheck($checks, 'update_directory', 'passed', 'Update directory exists and is writable.', [
                    'path' => $updateDirectory,
                ]);

                return;
            }

            $message = 'Update directory exists but is not writable: '.$updateDirectory;
            $errors[] = $message;
            $this->addCheck($checks, 'update_directory', 'failed', $message, [
                'path' => $updateDirectory,
            ]);

            return;
        }

        $parentDirectory = dirname($updateDirectory);

        if (is_dir($parentDirectory) && is_writable($parentDirectory)) {
            $message = 'Update directory does not exist yet, but its parent directory is writable.';
            $warnings[] = $message;
            $this->addCheck($checks, 'update_directory', 'warning', $message, [
                'path' => $updateDirectory,
                'parent' => $parentDirectory,
            ]);

            return;
        }

        $varDirectory = $projectDir.'/var';

        if (is_dir($varDirectory) && is_writable($varDirectory)) {
            $message = 'Update directory does not exist yet, but it can be created under var/.';
            $warnings[] = $message;
            $this->addCheck($checks, 'update_directory', 'warning', $message, [
                'path' => $updateDirectory,
                'parent' => $varDirectory,
            ]);

            return;
        }

        $message = 'Update directory does not exist and cannot be created with the current permissions.';
        $errors[] = $message;
        $this->addCheck($checks, 'update_directory', 'failed', $message, [
            'path' => $updateDirectory,
        ]);
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $errors
     * @param string[] $warnings
     */
    private function checkDiskSpace(
        string $updateDirectory,
        string $projectDir,
        ?string $packagePath,
        array &$checks,
        array &$errors,
        array &$warnings
    ): void {
        $diskPath = $this->findExistingDiskPath($updateDirectory, $projectDir);
        $freeSpace = disk_free_space($diskPath);

        if (false === $freeSpace) {
            $message = 'Unable to determine free disk space for update preflight.';
            $warnings[] = $message;
            $this->addCheck($checks, 'disk_space', 'warning', $message, [
                'path' => $diskPath,
            ]);

            return;
        }

        $requiredSpace = self::MINIMUM_FREE_SPACE_BYTES;
        $packageSize = null;

        if (null !== $packagePath && is_file($packagePath)) {
            $size = filesize($packagePath);

            if (false !== $size) {
                $packageSize = $size;
                $requiredSpace = max($requiredSpace, $size * self::PACKAGE_SPACE_MULTIPLIER);
            }
        }

        $details = [
            'path' => $diskPath,
            'free_bytes' => (int) $freeSpace,
            'required_bytes' => $requiredSpace,
            'package_size_bytes' => $packageSize,
        ];

        if ($freeSpace < $requiredSpace) {
            $message = 'Not enough free disk space for a safe update staging operation.';
            $errors[] = $message;
            $this->addCheck($checks, 'disk_space', 'failed', $message, $details);

            return;
        }

        $message = 'Free disk space is sufficient for this verification-stage update flow.';
        $this->addCheck($checks, 'disk_space', 'passed', $message, $details);
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $errors
     * @param string[] $warnings
     */
    private function checkPhpRequirement(UpdateManifest $manifest, array &$checks, array &$errors, array &$warnings): void
    {
        $requirements = $manifest->getRequirements();
        $phpRequirement = $requirements['php'] ?? null;

        if (!\is_string($phpRequirement) || '' === trim($phpRequirement)) {
            $message = 'Update manifest does not define a PHP version requirement.';
            $warnings[] = $message;
            $this->addCheck($checks, 'php_requirement', 'warning', $message, [
                'php_version' => PHP_VERSION,
            ]);

            return;
        }

        $matchResult = $this->matchesVersionRequirement(PHP_VERSION, $phpRequirement);

        if (null === $matchResult) {
            $message = 'Unable to fully evaluate PHP requirement: '.$phpRequirement;
            $warnings[] = $message;
            $this->addCheck($checks, 'php_requirement', 'warning', $message, [
                'php_version' => PHP_VERSION,
                'requirement' => $phpRequirement,
            ]);

            return;
        }

        if (!$matchResult) {
            $message = 'Current PHP version does not satisfy update requirement: '.$phpRequirement;
            $errors[] = $message;
            $this->addCheck($checks, 'php_requirement', 'failed', $message, [
                'php_version' => PHP_VERSION,
                'requirement' => $phpRequirement,
            ]);

            return;
        }

        $this->addCheck($checks, 'php_requirement', 'passed', 'Current PHP version satisfies the update requirement.', [
            'php_version' => PHP_VERSION,
            'requirement' => $phpRequirement,
        ]);
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $errors
     * @param string[] $warnings
     */
    private function checkPackagePath(?string $packagePath, array &$checks, array &$errors, array &$warnings): void
    {
        if (null === $packagePath || '' === trim($packagePath)) {
            $message = 'No local package path was provided. Downloaded packages can only be checked after download.';
            $warnings[] = $message;
            $this->addCheck($checks, 'package_path', 'warning', $message);

            return;
        }

        if (!is_file($packagePath) || !is_readable($packagePath)) {
            $message = 'Local update package is not readable: '.$packagePath;
            $errors[] = $message;
            $this->addCheck($checks, 'package_path', 'failed', $message, [
                'path' => $packagePath,
            ]);

            return;
        }

        if ('zip' !== strtolower(pathinfo($packagePath, PATHINFO_EXTENSION))) {
            $message = 'Local update package must be a ZIP archive.';
            $errors[] = $message;
            $this->addCheck($checks, 'package_path', 'failed', $message, [
                'path' => $packagePath,
            ]);

            return;
        }

        $this->addCheck($checks, 'package_path', 'passed', 'Local update package is readable.', [
            'path' => $packagePath,
            'size_bytes' => filesize($packagePath) ?: null,
        ]);
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $errors
     * @param string[] $warnings
     */
    private function checkVersionDirection(UpdateManifest $manifest, array &$checks, array &$errors, array &$warnings): void
    {
        $installedVersion = $this->getInstalledVersion();
        $targetVersion = $manifest->getVersion();

        if (!$this->isComparableVersion($installedVersion) || !$this->isComparableVersion($targetVersion)) {
            $message = 'Unable to compare installed and target versions automatically.';
            $warnings[] = $message;
            $this->addCheck($checks, 'version_direction', 'warning', $message, [
                'installed_version' => $installedVersion,
                'target_version' => $targetVersion,
            ]);

            return;
        }

        $compare = version_compare($targetVersion, $installedVersion);

        if ($compare < 0) {
            $message = 'Target version is lower than the installed version. Downgrades are not allowed by default.';
            $errors[] = $message;
            $this->addCheck($checks, 'version_direction', 'failed', $message, [
                'installed_version' => $installedVersion,
                'target_version' => $targetVersion,
            ]);

            return;
        }

        if (0 === $compare) {
            $message = 'Target version matches the installed version.';
            $warnings[] = $message;
            $this->addCheck($checks, 'version_direction', 'warning', $message, [
                'installed_version' => $installedVersion,
                'target_version' => $targetVersion,
            ]);

            return;
        }

        $this->addCheck($checks, 'version_direction', 'passed', 'Target version is newer than the installed version.', [
            'installed_version' => $installedVersion,
            'target_version' => $targetVersion,
        ]);
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $warnings
     */
    private function checkGitWorkingTree(string $projectDir, array &$checks, array &$warnings): void
    {
        if (!is_dir($projectDir.'/.git')) {
            $this->addCheck($checks, 'git_working_tree', 'passed', 'Project is not a Git checkout or .git is not present.');

            return;
        }

        if (!\function_exists('exec')) {
            $message = 'Unable to check Git working tree because exec() is disabled.';
            $warnings[] = $message;
            $this->addCheck($checks, 'git_working_tree', 'warning', $message);

            return;
        }

        $output = [];
        $exitCode = 0;
        exec('git -C '.escapeshellarg($projectDir).' status --porcelain 2>&1', $output, $exitCode);

        if (0 !== $exitCode) {
            $message = 'Unable to check Git working tree status.';
            $warnings[] = $message;
            $this->addCheck($checks, 'git_working_tree', 'warning', $message, [
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            return;
        }

        if ([] !== $output) {
            $message = 'Git working tree contains local changes. Automatic application should not proceed without explicit confirmation.';
            $warnings[] = $message;
            $this->addCheck($checks, 'git_working_tree', 'warning', $message, [
                'changed_entries' => \count($output),
            ]);

            return;
        }

        $this->addCheck($checks, 'git_working_tree', 'passed', 'Git working tree is clean.');
    }

    /**
     * @param array<int, array{key: string, status: string, message: string, details?: array<string, mixed>}> $checks
     * @param string[] $warnings
     */
    private function checkProjectMetadata(string $projectDir, array &$checks, array &$warnings): void
    {
        $missing = [];

        foreach (['composer.json', 'composer.lock', 'package.json', 'yarn.lock'] as $fileName) {
            if (!is_file($projectDir.'/'.$fileName)) {
                $missing[] = $fileName;
            }
        }

        if ([] !== $missing) {
            $message = 'Some project metadata files are missing. Later update stages may require manual dependency checks.';
            $warnings[] = $message;
            $this->addCheck($checks, 'project_metadata', 'warning', $message, [
                'missing' => $missing,
            ]);

            return;
        }

        $this->addCheck($checks, 'project_metadata', 'passed', 'Composer and Yarn metadata files are present.');
    }

    private function findExistingDiskPath(string $updateDirectory, string $projectDir): string
    {
        foreach ([$updateDirectory, dirname($updateDirectory), $projectDir.'/var', $projectDir] as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        return $projectDir;
    }

    private function getProjectDir(): string
    {
        return rtrim($this->kernel->getProjectDir(), '/');
    }

    private function getInstalledVersion(): string
    {
        try {
            $version = InstalledVersions::getPrettyVersion('chamilo/chamilo-lms');

            if (\is_string($version) && '' !== trim($version)) {
                return ltrim(trim($version), 'v');
            }
        } catch (Throwable) {
        }

        return 'unknown';
    }

    private function isComparableVersion(string $version): bool
    {
        return 1 === preg_match('/^\d+(?:\.\d+){1,3}(?:[-+][A-Za-z0-9.-]+)?$/', $version);
    }

    private function matchesVersionRequirement(string $version, string $requirement): ?bool
    {
        $tokens = preg_split('/[\s,]+/', trim($requirement), -1, PREG_SPLIT_NO_EMPTY);

        if (false === $tokens || [] === $tokens) {
            return null;
        }

        foreach ($tokens as $token) {
            $matches = $this->matchesVersionConstraint($version, $token);

            if (null === $matches) {
                return null;
            }

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    private function matchesVersionConstraint(string $version, string $constraint): ?bool
    {
        if (1 === preg_match('/^(>=|>|<=|<|==|=)([0-9][A-Za-z0-9.+-]*)$/', $constraint, $matches)) {
            $operator = '=' === $matches[1] ? '==' : $matches[1];

            return version_compare($version, $matches[2], $operator);
        }

        if (1 === preg_match('/^\^([0-9]+)(?:\.([0-9]+))?(?:\.([0-9]+))?$/', $constraint, $matches)) {
            $major = (int) $matches[1];
            $minor = isset($matches[2]) ? (int) $matches[2] : 0;
            $patch = isset($matches[3]) ? (int) $matches[3] : 0;
            $lowerBound = $major.'.'.$minor.'.'.$patch;
            $upperBound = ($major + 1).'.0.0';

            return version_compare($version, $lowerBound, '>=') && version_compare($version, $upperBound, '<');
        }

        if (1 === preg_match('/^[0-9][A-Za-z0-9.+-]*$/', $constraint)) {
            return version_compare($version, $constraint, '==');
        }

        return null;
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
}
