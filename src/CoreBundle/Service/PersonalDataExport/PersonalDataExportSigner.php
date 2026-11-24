<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\PersonalDataExport;

use Chamilo\CoreBundle\Settings\SettingsManager;
use InvalidArgumentException;
use RuntimeException;

/**
 * HMAC-SHA256 signatures for personal-data portability requests.
 *
 * A dedicated platform secret is used, not the application secret, so that
 * administrators can share and rotate it with receiving services independently.
 * If the setting is empty, a new secret is generated and stored on first use.
 *
 * The signed string is:
 *
 *     {timestamp}\n{nonce}\n{sha256_hex(raw_body)}
 *
 * Headers sent with the request:
 * - X-Chamilo-Signature: sha256=<hex HMAC>
 * - X-Chamilo-Timestamp: Unix timestamp
 * - X-Chamilo-Nonce: unique request id
 */
final class PersonalDataExportSigner
{
    public const string HEADER_SIGNATURE = 'X-Chamilo-Signature';
    public const string HEADER_TIMESTAMP = 'X-Chamilo-Timestamp';
    public const string HEADER_NONCE = 'X-Chamilo-Nonce';
    public const string SETTING_SECRET = 'privacy.personal_data_export_hmac_secret';

    public function __construct(
        private readonly SettingsManager $settingsManager
    ) {}

    /**
     * @return array{signature: string, timestamp: int, nonce: string, headers: array<string, string>}
     */
    public function sign(string $body, ?int $timestamp = null, ?string $nonce = null): array
    {
        $timestamp ??= time();
        $nonce = (null === $nonce || '' === $nonce) ? bin2hex(random_bytes(16)) : $nonce;
        $signature = $this->computeSignature($body, $timestamp, $nonce);

        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'headers' => [
                self::HEADER_SIGNATURE => 'sha256='.$signature,
                self::HEADER_TIMESTAMP => (string) $timestamp,
                self::HEADER_NONCE => $nonce,
            ],
        ];
    }

    public function computeSignature(string $body, int $timestamp, string $nonce): string
    {
        return hash_hmac('sha256', $this->payload($body, $timestamp, $nonce), $this->getSecret());
    }

    public function payload(string $body, int $timestamp, string $nonce): string
    {
        return $timestamp."\n".$nonce."\n".hash('sha256', $body);
    }

    public function getSecret(): string
    {
        $secret = trim((string) $this->settingsManager->getSetting(self::SETTING_SECRET, true));
        if ('' !== $secret) {
            return $secret;
        }

        try {
            $secret = bin2hex(random_bytes(32));
            $this->settingsManager->updateSetting(self::SETTING_SECRET, $secret);
        } catch (InvalidArgumentException $exception) {
            throw new RuntimeException('The personal data export HMAC secret is not configured. Set privacy.personal_data_export_hmac_secret.', 0, $exception);
        }

        return $secret;
    }
}
