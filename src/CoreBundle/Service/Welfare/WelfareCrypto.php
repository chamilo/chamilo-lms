<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Welfare;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

use const SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES;
use const SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES;
use const SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;

/**
 * Versioned authenticated encryption. The key lives in var/ outside the webroot
 * and outside the database. Never recreate a lost key when reports already exist.
 */
final class WelfareCrypto
{
    private ?string $key = null;

    public function __construct(
        private readonly ?string $encodedKey = null,
        private readonly ?SettingsManager $settingsManager = null,
        #[Autowire('%kernel.project_dir%')]
        private readonly ?string $projectDir = null,
        private readonly ?EntityManagerInterface $entityManager = null,
    ) {}

    public function assertEnabled(): void
    {
        if (
            null === $this->encodedKey
            && (
                null === $this->settingsManager
                || 'true' !== (string) $this->settingsManager->getSetting('security.confidential_reporting_enabled')
            )
        ) {
            throw new ServiceUnavailableHttpException(null, 'Confidential reporting is not enabled.');
        }

        $this->key();
    }

    /**
     * Navigation hint only; access is rechecked on all operational endpoints.
     */
    public function isReady(): bool
    {
        try {
            $this->assertEnabled();

            return true;
        } catch (ServiceUnavailableHttpException) {
            return false;
        }
    }

    public function encrypt(string $plaintext, string $context): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext,
            $context,
            $nonce,
            $this->key(),
        );

        return 'v1:'.base64_encode($nonce.$ciphertext);
    }

    public function decrypt(string $value, string $context): string
    {
        if (!str_starts_with($value, 'v1:')) {
            throw new RuntimeException('Unsupported confidential content version.');
        }

        $data = base64_decode(substr($value, 3), true);
        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (false === $data || \strlen($data) < $nonceLength + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES) {
            throw new RuntimeException('Invalid confidential content.');
        }

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($data, $nonceLength),
            $context,
            substr($data, 0, $nonceLength),
            $this->key(),
        );
        if (!\is_string($plaintext)) {
            throw new RuntimeException('Confidential content authentication failed.');
        }

        return $plaintext;
    }

    public function reporterDigest(int $accessUrlId, int $userId): string
    {
        $indexKey = hash_hkdf('sha256', $this->key(), 32, 'chamilo-welfare-reporter-v1');

        return hash_hmac('sha256', $accessUrlId.':'.$userId, $indexKey);
    }

    private function key(): string
    {
        if (null === $this->key) {
            if (null !== $this->encodedKey) {
                $this->key = $this->decodeKey($this->encodedKey);
            } else {
                $this->key = $this->privateKey();
            }
        }

        return $this->key;
    }

    private function decodeKey(string $encoded): string
    {
        $key = base64_decode(trim($encoded), true);
        if (false === $key || SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES !== \strlen($key)) {
            throw new ServiceUnavailableHttpException(null, 'Confidential reporting encryption key is not configured.');
        }

        return $key;
    }

    private function privateKey(): string
    {
        if (null === $this->projectDir || null === $this->entityManager) {
            throw new ServiceUnavailableHttpException(null, 'Confidential key storage is unavailable.');
        }

        $directory = $this->projectDir.'/var/welfare-keys';
        $path = $directory.'/master.key';
        if (is_link($directory) || (file_exists($path) && is_link($path))) {
            throw new ServiceUnavailableHttpException(null, 'Confidential key storage is not secure.');
        }

        if (is_file($path)) {
            $encoded = @file_get_contents($path);
            if (false === $encoded) {
                throw new ServiceUnavailableHttpException(null, 'Confidential key storage is unreadable.');
            }
            $this->assertPrivatePermissions($directory, $path);
            $key = $this->decodeKey($encoded);
            $firstReport = $this->firstReport();
            if (null !== $firstReport && !$this->matchesFirstReport($firstReport, $key)) {
                throw new ServiceUnavailableHttpException(null, 'Confidential key does not match existing reports.');
            }

            return $key;
        }

        $firstReport = $this->firstReport();
        // Transitional import: the previous implementation read a key from the
        // process environment. Copy it to private storage once; never rotate it.
        $legacyEncoded = (string) ($_SERVER['CHAMILO_WELFARE_KEY'] ?? $_ENV['CHAMILO_WELFARE_KEY'] ?? getenv('CHAMILO_WELFARE_KEY') ?: '');
        if ('' !== $legacyEncoded) {
            $key = $this->decodeKey($legacyEncoded);
            if (null !== $firstReport && !$this->matchesFirstReport($firstReport, $key)) {
                throw new ServiceUnavailableHttpException(null, 'The existing confidential reports require their original encryption key.');
            }
        } else {
            if (null !== $firstReport) {
                throw new ServiceUnavailableHttpException(null, 'The confidential reporting encryption key is missing. Existing reports were not altered.');
            }
            $key = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES);
        }

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new ServiceUnavailableHttpException(null, 'Private confidential key directory is not writable.');
        }
        if (is_link($directory)) {
            throw new ServiceUnavailableHttpException(null, 'Confidential key directory is not secure.');
        }

        $handle = @fopen($path, 'x');
        if (false === $handle) {
            // A parallel request could have initialized the key first.
            if (is_file($path) && !is_link($path)) {
                $stored = @file_get_contents($path);
                if (false !== $stored) {
                    $this->assertPrivatePermissions($directory, $path);
                    $otherKey = $this->decodeKey($stored);
                    if (null !== $firstReport && !$this->matchesFirstReport($firstReport, $otherKey)) {
                        throw new ServiceUnavailableHttpException(null, 'Confidential key does not match existing reports.');
                    }

                    return $otherKey;
                }
            }

            throw new ServiceUnavailableHttpException(null, 'Private confidential key storage is not writable.');
        }

        try {
            @chmod($path, 0600);
            $encoded = base64_encode($key)."\n";
            if (\strlen($encoded) !== fwrite($handle, $encoded) || !fflush($handle)) {
                throw new RuntimeException('Cannot initialize the confidential encryption key.');
            }
        } catch (Throwable $exception) {
            fclose($handle);
            @unlink($path);

            throw new ServiceUnavailableHttpException(null, 'Unable to initialize private confidential key storage.', $exception);
        }
        fclose($handle);
        $this->assertPrivatePermissions($directory, $path);

        return $key;
    }

    /**
     * @return array{reference_token:string,subject_cipher:string}|null
     */
    private function firstReport(): ?array
    {
        try {
            $row = $this->entityManager->getConnection()->fetchAssociative(
                'SELECT reference_token, subject_cipher FROM welfare_report ORDER BY id LIMIT 1'
            );
        } catch (Throwable $exception) {
            throw new ServiceUnavailableHttpException(null, 'Confidential reporting database is not initialized.', $exception);
        }

        return false === $row ? null : $row;
    }

    /**
     * @param array{reference_token:string,subject_cipher:string} $row
     */
    private function matchesFirstReport(array $row, string $key): bool
    {
        $cipher = (string) $row['subject_cipher'];
        $raw = str_starts_with($cipher, 'v1:') ? base64_decode(substr($cipher, 3), true) : false;
        $size = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (false === $raw || \strlen($raw) < $size + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES) {
            return false;
        }

        return false !== sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($raw, $size),
            'subject:'.$row['reference_token'],
            substr($raw, 0, $size),
            $key,
        );
    }

    private function assertPrivatePermissions(string $directory, string $path): void
    {
        $fileMode = @fileperms($path);
        $dirMode = @fileperms($directory);
        if (false === $fileMode || false === $dirMode || (0 !== ($fileMode & 0077)) || (0 !== ($dirMode & 0077))) {
            throw new ServiceUnavailableHttpException(null, 'Confidential key permissions must be 0600 (file) and 0700 (directory).');
        }
    }
}
