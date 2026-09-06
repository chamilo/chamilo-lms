<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class UpdateConfiguration
{
    public const string OFFICIAL_MANIFEST_SOURCE = 'https://updates.chamilo.org/latest-stable.json';

    private const string LOCAL_TEST_MANIFEST_SOURCE = '/tmp/chamilo-update-slow-manifest.json';
    private const string LOCAL_TEST_PACKAGE_PATH = '/tmp/chamilo-update-slow.zip';
    private const int DEBUG_SLOW_COPY_MS = 0;
    private const int COMMAND_TIMEOUT_SECONDS = 900;

    public function __construct(
        #[Autowire(param: 'kernel.environment')]
        private string $environment,
        #[Autowire('%env(default::CHAMILO_UPDATE_DEVELOPMENT_TOOLS)%')]
        private ?string $developmentUpdateTools = null,
    ) {}

    public function getDefaultManifestSource(): ?string
    {
        return self::OFFICIAL_MANIFEST_SOURCE;
    }

    public function getOfficialManifestSource(): ?string
    {
        return self::OFFICIAL_MANIFEST_SOURCE;
    }

    public function getOfficialManifestOrigin(): string
    {
        $parts = parse_url(self::OFFICIAL_MANIFEST_SOURCE);

        if (!\is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ('' === $scheme || '' === $host) {
            return '';
        }

        $port = isset($parts['port']) ? ':'.(string) $parts['port'] : '';

        return $scheme.'://'.$host.$port;
    }

    public function isAllowedOfficialManifestUrl(string $source): bool
    {
        return $this->isAllowedOfficialUpdateUrl($source);
    }

    public function isAllowedOfficialUpdateUrl(string $source): bool
    {
        $sourceParts = parse_url(trim($source));
        $officialParts = parse_url(self::OFFICIAL_MANIFEST_SOURCE);

        if (!\is_array($sourceParts) || !\is_array($officialParts)) {
            return false;
        }

        if (isset($sourceParts['user']) || isset($sourceParts['pass'])) {
            return false;
        }

        $sourceScheme = strtolower((string) ($sourceParts['scheme'] ?? ''));
        $officialScheme = strtolower((string) ($officialParts['scheme'] ?? ''));
        $sourceHost = strtolower((string) ($sourceParts['host'] ?? ''));
        $officialHost = strtolower((string) ($officialParts['host'] ?? ''));

        if ('' === $sourceScheme || '' === $sourceHost) {
            return false;
        }

        if ($sourceScheme !== $officialScheme || $sourceHost !== $officialHost) {
            return false;
        }

        $sourcePort = $sourceParts['port'] ?? ('https' === $sourceScheme ? 443 : null);
        $officialPort = $officialParts['port'] ?? ('https' === $officialScheme ? 443 : null);

        return $sourcePort === $officialPort;
    }

    public function getLocalTestManifestSource(): ?string
    {
        if (!$this->allowsDevelopmentUpdateTools()) {
            return null;
        }

        return self::LOCAL_TEST_MANIFEST_SOURCE;
    }

    public function getLocalTestPackagePath(): ?string
    {
        if (!$this->allowsDevelopmentUpdateTools()) {
            return null;
        }

        return self::LOCAL_TEST_PACKAGE_PATH;
    }

    public function allowsDevelopmentUpdateTools(): bool
    {
        return $this->readBoolean($this->developmentUpdateTools);
    }

    public function allowsLocalPaths(): bool
    {
        return $this->allowsDevelopmentUpdateTools();
    }

    public function allowsSkipSignature(): bool
    {
        return $this->allowsDevelopmentUpdateTools();
    }

    public function isProduction(): bool
    {
        return 'prod' === $this->environment;
    }

    public function getDebugSlowCopyMilliseconds(): int
    {
        if (!$this->allowsDevelopmentUpdateTools()) {
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

    private function readBoolean(?string $value): bool
    {
        $value = strtolower((string) $this->normalizeOptionalString($value));

        return \in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);

        return '' !== $value ? $value : null;
    }
}
