<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class UpdateConfiguration
{
    public const OFFICIAL_MANIFEST_SOURCE = 'https://updates.chamilo.org/2.x/stable.json';

    /**
     * Internal development-only switch.
     *
     * Keep disabled in committed code. Enable locally only when testing local
     * packages, unsigned packages or simulated update notices.
     */
    public const ENABLE_DEVELOPMENT_UPDATE_TOOLS = false;

    private const LOCAL_TEST_MANIFEST_SOURCE = '/tmp/chamilo-update-slow-manifest.json';
    private const LOCAL_TEST_PACKAGE_PATH = '/tmp/chamilo-update-slow.zip';
    private const DEBUG_SLOW_COPY_MS = 0;
    private const COMMAND_TIMEOUT_SECONDS = 900;

    public function __construct(
        #[Autowire(param: 'kernel.environment')]
        private string $environment,
    ) {}

    public function getDefaultManifestSource(): ?string
    {
        return self::OFFICIAL_MANIFEST_SOURCE;
    }

    public function getOfficialManifestSource(): ?string
    {
        return self::OFFICIAL_MANIFEST_SOURCE;
    }

    public function getLocalTestManifestSource(): ?string
    {
        if (!self::ENABLE_DEVELOPMENT_UPDATE_TOOLS) {
            return null;
        }

        return self::LOCAL_TEST_MANIFEST_SOURCE;
    }

    public function getLocalTestPackagePath(): ?string
    {
        if (!self::ENABLE_DEVELOPMENT_UPDATE_TOOLS) {
            return null;
        }

        return self::LOCAL_TEST_PACKAGE_PATH;
    }

    public function allowsDevelopmentUpdateTools(): bool
    {
        return self::ENABLE_DEVELOPMENT_UPDATE_TOOLS;
    }

    public function getTrustedPublicKey(): ?string
    {
        return null;
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
        return self::ENABLE_DEVELOPMENT_UPDATE_TOOLS;
    }

    public function allowsSkipSignature(): bool
    {
        return self::ENABLE_DEVELOPMENT_UPDATE_TOOLS;
    }

    public function isProduction(): bool
    {
        return 'prod' === $this->environment;
    }

    public function getDebugSlowCopyMilliseconds(): int
    {
        if (!self::ENABLE_DEVELOPMENT_UPDATE_TOOLS) {
            return 0;
        }

        return min(max(self::DEBUG_SLOW_COPY_MS, 0), 5000);
    }

    public function allowsUiPostApplyCommands(): bool
    {
        return true;
    }

    public function getCommandTimeoutSeconds(): int
    {
        if (self::COMMAND_TIMEOUT_SECONDS < 60) {
            return 60;
        }

        return min(self::COMMAND_TIMEOUT_SECONDS, 7200);
    }
}
