<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Dto;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use InvalidArgumentException;

#[Exclude]
final readonly class UpdateManifest
{
    public function __construct(
        private string $channel,
        private string $version,
        private string $releasedAt,
        private string $packageUrl,
        private string $packageSha256,
        private ?string $signatureType = null,
        private ?string $signatureUrl = null,
        private ?string $signatureKeyId = null,
        private array $requirements = [],
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $package = $data['package'] ?? null;

        if (!\is_array($package)) {
            throw new InvalidArgumentException('Update manifest is missing the package block.');
        }

        $signature = $data['signature'] ?? [];
        if (null !== $signature && !\is_array($signature)) {
            throw new InvalidArgumentException('Update manifest signature block must be an object.');
        }

        $requirements = $data['requirements'] ?? [];
        if (null !== $requirements && !\is_array($requirements)) {
            throw new InvalidArgumentException('Update manifest requirements block must be an object.');
        }

        $channel = self::readRequiredString($data, 'channel');
        $version = self::readRequiredString($data, 'version');
        $releasedAt = self::readRequiredString($data, 'released_at');
        $packageUrl = self::readRequiredString($package, 'url');
        $packageSha256 = strtolower(self::readRequiredString($package, 'sha256'));

        if (1 !== preg_match('/^[a-f0-9]{64}$/', $packageSha256)) {
            throw new InvalidArgumentException('Update package sha256 must be a 64-character hexadecimal string.');
        }

        return new self(
            $channel,
            $version,
            $releasedAt,
            $packageUrl,
            $packageSha256,
            self::readOptionalString($signature, 'type'),
            self::readOptionalString($signature, 'url'),
            self::readOptionalString($signature, 'key_id'),
            $requirements,
        );
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getReleasedAt(): string
    {
        return $this->releasedAt;
    }

    public function getPackageUrl(): string
    {
        return $this->packageUrl;
    }

    public function getPackageSha256(): string
    {
        return $this->packageSha256;
    }

    public function getSignatureType(): ?string
    {
        return $this->signatureType;
    }

    public function getSignatureUrl(): ?string
    {
        return $this->signatureUrl;
    }

    public function getSignatureKeyId(): ?string
    {
        return $this->signatureKeyId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function requiresSignature(): bool
    {
        return null !== $this->signatureType && '' !== trim($this->signatureType);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function readRequiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (!\is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException('Update manifest is missing required string field "'.$key.'".');
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private static function readOptionalString(?array $data, string $key): ?string
    {
        if (null === $data) {
            return null;
        }

        $value = $data[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new InvalidArgumentException('Update manifest field "'.$key.'" must be a string.');
        }

        $value = trim($value);

        return '' !== $value ? $value : null;
    }
}
