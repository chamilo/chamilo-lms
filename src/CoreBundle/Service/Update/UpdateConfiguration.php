<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class UpdateConfiguration
{
    private const ENV_ALLOW_LOCAL_PATHS = 'CHAMILO_UPDATE_ALLOW_LOCAL_PATHS';
    private const ENV_ALLOW_SKIP_SIGNATURE = 'CHAMILO_UPDATE_ALLOW_SKIP_SIGNATURE';
    private const ENV_MANIFEST_URL = 'CHAMILO_UPDATE_MANIFEST_URL';
    private const ENV_MINISIGN_PUBLIC_KEY = 'CHAMILO_UPDATE_MINISIGN_PUBLIC_KEY';
    private const ENV_DEBUG_SLOW_COPY_MS = 'CHAMILO_UPDATE_DEBUG_SLOW_COPY_MS';
    private const ENV_COMMAND_TIMEOUT = 'CHAMILO_UPDATE_COMMAND_TIMEOUT';
    private const LOCAL_TEST_MANIFEST_SOURCE = '/tmp/chamilo-update-slow-manifest.json';
    private const LOCAL_TEST_PACKAGE_PATH = '/tmp/chamilo-update-slow.zip';

    public function __construct(
        #[Autowire(param: 'kernel.environment')]
        private string $environment,
    ) {}

    public function getDefaultManifestSource(): ?string
    {
        return $this->readStringEnv(self::ENV_MANIFEST_URL);
    }

    public function getOfficialManifestSource(): ?string
    {
        return $this->getDefaultManifestSource();
    }

    public function getLocalTestManifestSource(): ?string
    {
        if ($this->isProduction()) {
            return null;
        }

        return self::LOCAL_TEST_MANIFEST_SOURCE;
    }

    public function getLocalTestPackagePath(): ?string
    {
        if ($this->isProduction()) {
            return null;
        }

        return self::LOCAL_TEST_PACKAGE_PATH;
    }

    public function getTrustedPublicKey(): ?string
    {
        return $this->readStringEnv(self::ENV_MINISIGN_PUBLIC_KEY);
    }

    public function hasTrustedPublicKey(): bool
    {
        return null !== $this->getTrustedPublicKey();
    }

    public function getTrustedPublicKeyFingerprint(): ?string
    {
        $trustedPublicKey = $this->getTrustedPublicKey();

        if (null === $trustedPublicKey) {
            return null;
        }

        return 'sha256:'.substr(hash('sha256', $trustedPublicKey), 0, 16);
    }

    public function allowsLocalPaths(): bool
    {
        return $this->readBooleanEnv(self::ENV_ALLOW_LOCAL_PATHS, !$this->isProduction());
    }

    public function allowsSkipSignature(): bool
    {
        return $this->readBooleanEnv(self::ENV_ALLOW_SKIP_SIGNATURE, !$this->isProduction());
    }

    public function isProduction(): bool
    {
        return 'prod' === $this->environment;
    }

    public function getDebugSlowCopyMilliseconds(): int
    {
        if ($this->isProduction()) {
            return 0;
        }

        return min($this->readIntegerEnv(self::ENV_DEBUG_SLOW_COPY_MS, 0), 5000);
    }

    public function allowsUiPostApplyCommands(): bool
    {
        return !$this->isProduction();
    }

    public function getCommandTimeoutSeconds(): int
    {
        $timeout = $this->readIntegerEnv(self::ENV_COMMAND_TIMEOUT, 900);

        if ($timeout < 60) {
            return 60;
        }

        return min($timeout, 7200);
    }

    private function readStringEnv(string $name): ?string
    {
        foreach ([$_ENV[$name] ?? null, $_SERVER[$name] ?? null, getenv($name)] as $value) {
            if (!\is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ('' !== $value) {
                return $value;
            }
        }

        return null;
    }

    private function readBooleanEnv(string $name, bool $default): bool
    {
        $value = $this->readStringEnv($name);

        if (null === $value) {
            return $default;
        }

        return \in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    private function readIntegerEnv(string $name, int $default): int
    {
        $value = $this->readStringEnv($name);

        if (null === $value || !ctype_digit($value)) {
            return $default;
        }

        return max((int) $value, 0);
    }
}
