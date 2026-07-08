<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Composer\InstalledVersions;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

final readonly class InstalledChamiloVersionProvider
{
    public function __construct(
        private KernelInterface $kernel,
    ) {}

    public function getInstalledVersion(): string
    {
        foreach ([
            $this->getInstalledVersionFromVersionFile(),
            $this->getInstalledVersionFromLegacyConfiguration(),
            $this->getInstalledVersionFromComposerMetadata(),
            $this->getInstalledVersionFromGitTag($this->getProjectDir()),
        ] as $candidate) {
            $version = $this->normalizeVersion((string) $candidate);

            if (null !== $version) {
                return $version;
            }
        }

        return 'unknown';
    }

    public function isComparableVersion(string $version): bool
    {
        return 1 === preg_match('/^\d+(?:\.\d+){1,3}(?:[-+][A-Za-z0-9.-]+)?$/', $version);
    }

    public function normalizeVersion(string $version): ?string
    {
        $version = trim($version);

        if ('' === $version) {
            return null;
        }

        if (1 === preg_match('/v?(\d+(?:\.\d+){1,3}(?:[-+][A-Za-z0-9.-]+)?)/', $version, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function getProjectDir(): string
    {
        return rtrim($this->kernel->getProjectDir(), '/');
    }

    private function getInstalledVersionFromVersionFile(): ?string
    {
        $versionFile = $this->getProjectDir().'/public/main/install/version.php';

        if (!is_file($versionFile) || !is_readable($versionFile)) {
            return null;
        }

        $bufferLevel = ob_get_level();
        ob_start();

        try {
            $versionDetails = include $versionFile;
        } catch (Throwable) {
            $this->cleanOutputBuffer($bufferLevel);

            return null;
        }

        $this->cleanOutputBuffer($bufferLevel);

        if (!\is_array($versionDetails)) {
            return null;
        }

        foreach (['new_version', 'system_version', 'version'] as $key) {
            if (!isset($versionDetails[$key])) {
                continue;
            }

            $version = $versionDetails[$key];

            if ((\is_string($version) || is_numeric($version)) && '' !== trim((string) $version)) {
                return (string) $version;
            }
        }

        return null;
    }

    private function getInstalledVersionFromLegacyConfiguration(): ?string
    {
        if (!\function_exists('api_get_version')) {
            return null;
        }

        try {
            $version = api_get_version();

            if (\is_string($version) && '' !== trim($version)) {
                return $version;
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function getInstalledVersionFromComposerMetadata(): ?string
    {
        try {
            $version = InstalledVersions::getPrettyVersion('chamilo/chamilo-lms');

            if (\is_string($version) && '' !== trim($version)) {
                return $version;
            }
        } catch (Throwable) {
        }

        $composerJsonPath = $this->getProjectDir().'/composer.json';

        if (!is_file($composerJsonPath) || !is_readable($composerJsonPath)) {
            return null;
        }

        $composerJson = file_get_contents($composerJsonPath);

        if (false === $composerJson) {
            return null;
        }

        $decoded = json_decode($composerJson, true);

        if (!\is_array($decoded) || !isset($decoded['version']) || !\is_string($decoded['version'])) {
            return null;
        }

        return $decoded['version'];
    }

    private function getInstalledVersionFromGitTag(string $projectDir): ?string
    {
        if (!is_dir($projectDir) || !\function_exists('exec')) {
            return null;
        }

        $output = [];
        $exitCode = 0;
        exec('git -C '.escapeshellarg($projectDir).' describe --tags --abbrev=0 2>&1', $output, $exitCode);

        if (0 === $exitCode && [] !== $output) {
            return trim((string) $output[0]);
        }

        $output = [];
        $exitCode = 0;
        exec('git -C '.escapeshellarg($projectDir).' describe --tags --always --dirty 2>&1', $output, $exitCode);

        if (0 !== $exitCode || [] === $output) {
            return null;
        }

        return trim((string) $output[0]);
    }

    private function cleanOutputBuffer(int $bufferLevel): void
    {
        while (ob_get_level() > $bufferLevel) {
            ob_end_clean();
        }
    }
}
