<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Security;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

use const SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES;
use const SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES;
use const SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;

/**
 * Authenticated encryption with a separate, non-web-accessible key.
 * Key initialization is explicit and never happens during a web request.
 */
final class AtRestCrypto
{
    public const string PREFIX = 'chamilo-encrypted:v1:';

    private ?string $key = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    public function isEncrypted(string $value): bool
    {
        // Unknown versions must fail closed rather than become readable plaintext.
        return str_starts_with($value, 'chamilo-encrypted:');
    }

    public function encrypt(string $plaintext, string $context): string
    {
        $this->assertContext($context);
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext,
            $context,
            $nonce,
            $this->key(),
        );

        return self::PREFIX.base64_encode($nonce.$ciphertext);
    }

    public function decrypt(string $value, string $context): string
    {
        $this->assertContext($context);
        if (!str_starts_with($value, self::PREFIX)) {
            throw new RuntimeException('Unsupported encrypted data version.');
        }

        $raw = base64_decode(substr($value, \strlen(self::PREFIX)), true);
        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if (false === $raw || \strlen($raw) < $nonceLength + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES) {
            throw new RuntimeException('Invalid encrypted data.');
        }

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($raw, $nonceLength),
            $context,
            substr($raw, 0, $nonceLength),
            $this->key(),
        );
        if (false === $plaintext) {
            throw new RuntimeException('Encrypted data authentication failed.');
        }

        return $plaintext;
    }

    /**
     * Initialize the key once, explicitly. Never replace an existing key.
     * The command checks for encrypted database rows before calling this.
     */
    public function initializeKey(): void
    {
        [$directory, $path] = $this->paths();
        if (is_link($directory) || file_exists($path) || is_link($path)) {
            throw new RuntimeException('Encryption key storage already exists or is unsafe.');
        }

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create encryption key directory.');
        }
        $this->assertDirectoryPermissions($directory);

        $handle = @fopen($path, 'x');
        if (false === $handle) {
            throw new RuntimeException('Cannot create encryption key exclusively.');
        }

        try {
            if (!@chmod($path, 0600)) {
                throw new RuntimeException('Cannot secure encryption key permissions.');
            }
            $encoded = base64_encode(random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES))."\n";
            if (\strlen($encoded) !== fwrite($handle, $encoded) || !fflush($handle)) {
                throw new RuntimeException('Cannot write encryption key.');
            }
        } catch (Throwable $exception) {
            fclose($handle);
            @unlink($path);

            throw new RuntimeException('Failed to initialize encryption key.', 0, $exception);
        }

        fclose($handle);
        $this->key = null;
        $this->key();
    }

    public function hasKey(): bool
    {
        [$directory, $path] = $this->paths();
        if (is_link($directory) || is_link($path)) {
            throw new RuntimeException('Encryption key storage is unsafe.');
        }

        return is_file($path);
    }

    private function key(): string
    {
        if (null !== $this->key) {
            return $this->key;
        }

        [$directory, $path] = $this->paths();
        $this->assertDirectoryPermissions($directory);
        if (is_link($path) || !is_file($path)) {
            throw new RuntimeException('Encryption key is missing. Restore the original key from backup.');
        }

        $mode = @fileperms($path);
        if (false === $mode || 0 !== ($mode & 0077)) {
            throw new RuntimeException('Encryption key permissions must be 0600.');
        }

        $encoded = @file_get_contents($path);
        if (false === $encoded) {
            throw new RuntimeException('Encryption key is not readable by this process.');
        }
        $key = base64_decode(trim($encoded), true);
        if (false === $key || SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES !== \strlen($key)) {
            throw new RuntimeException('Invalid encryption key.');
        }

        return $this->key = $key;
    }

    private function assertDirectoryPermissions(string $directory): void
    {
        if (is_link($directory) || !is_dir($directory)) {
            throw new RuntimeException('Private encryption key directory is missing or unsafe.');
        }
        $mode = @fileperms($directory);
        if (false === $mode || 0 !== ($mode & 0077)) {
            throw new RuntimeException('Encryption key directory permissions must be 0700.');
        }
    }

    /**
     * @return array{string, string}
     */
    private function paths(): array
    {
        $directory = $this->projectDir.'/var/data-at-rest';

        return [$directory, $directory.'/master.key'];
    }

    private function assertContext(string $context): void
    {
        if ('' === $context) {
            throw new RuntimeException('Encryption context is required.');
        }
    }
}
