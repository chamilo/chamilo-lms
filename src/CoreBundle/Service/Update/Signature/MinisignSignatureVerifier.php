<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Update\Signature;

use Chamilo\CoreBundle\Service\Update\Dto\UpdateManifest;
use Chamilo\CoreBundle\Service\Update\Dto\UpdatePackageVerificationResult;
use InvalidArgumentException;
use RuntimeException;
use SodiumException;

/**
 * Verifies Minisign (Ed25519) signatures natively, without shelling out to the
 * `minisign` CLI binary, which is not installed on most production hosts.
 *
 * Format reference: https://jedisct1.github.io/minisign/, cross-checked against
 * jedisct1/minisign's minisign.c/minisign.h (sig_load(), verify()).
 */
final class MinisignSignatureVerifier implements UpdateSignatureVerifierInterface
{
    private const string ALGORITHM_LEGACY = 'Ed';
    private const string ALGORITHM_PREHASHED = 'ED';
    private const string UNTRUSTED_COMMENT_PREFIX = 'untrusted comment: ';
    private const string TRUSTED_COMMENT_PREFIX = 'trusted comment: ';
    private const int KEY_ID_BYTES = 8;
    private const int PUBLIC_KEY_BYTES = 32;
    private const int SIGNATURE_BYTES = 64;
    private const int PREHASH_BYTES = 64;
    private const int PUBLIC_KEY_BLOB_BYTES = 2 + self::KEY_ID_BYTES + self::PUBLIC_KEY_BYTES;
    private const int SIGNATURE_BLOB_BYTES = 2 + self::KEY_ID_BYTES + self::SIGNATURE_BYTES;
    private const int STREAM_CHUNK_BYTES = 1_048_576;

    public function supports(UpdateManifest $manifest): bool
    {
        return 'minisign' === strtolower((string) $manifest->getSignatureType());
    }

    public function verify(
        string $packagePath,
        string $signaturePath,
        UpdateManifest $manifest,
        ?string $trustedPublicKey
    ): UpdatePackageVerificationResult {
        if (!\function_exists('sodium_crypto_sign_verify_detached')) {
            return UpdatePackageVerificationResult::failure([
                'The sodium PHP extension is required to verify Minisign signatures.',
            ]);
        }

        if (null === $trustedPublicKey || '' === trim($trustedPublicKey)) {
            return UpdatePackageVerificationResult::failure([
                'A trusted Minisign public key is required to verify the update package.',
            ]);
        }

        if (!is_file($packagePath) || !is_readable($packagePath)) {
            return UpdatePackageVerificationResult::failure([
                'Update package file is not readable: '.$packagePath,
            ]);
        }

        if (!is_file($signaturePath) || !is_readable($signaturePath)) {
            return UpdatePackageVerificationResult::failure([
                'Minisign signature file is not readable: '.$signaturePath,
            ]);
        }

        $details = ['signature_type' => $manifest->getSignatureType()];

        try {
            $publicKey = $this->decodePublicKey($trustedPublicKey);
            $signature = $this->parseSignatureFile($signaturePath);
        } catch (InvalidArgumentException $exception) {
            return UpdatePackageVerificationResult::failure([$exception->getMessage()], $details);
        }

        if (!hash_equals($publicKey['keyId'], $signature['keyId'])) {
            return UpdatePackageVerificationResult::failure([
                \sprintf(
                    'Minisign signature key id %s does not match the trusted key id %s.',
                    $this->formatKeyId($signature['keyId']),
                    $this->formatKeyId($publicKey['keyId']),
                ),
            ], $details);
        }

        try {
            $message = self::ALGORITHM_PREHASHED === $signature['algorithm']
                ? $this->hashFile($packagePath)
                : $this->readFile($packagePath);
        } catch (RuntimeException $exception) {
            return UpdatePackageVerificationResult::failure([$exception->getMessage()], $details);
        }

        try {
            $fileSignatureValid = sodium_crypto_sign_verify_detached(
                $signature['signature'],
                $message,
                $publicKey['publicKey'],
            );

            // The trusted comment carries its own signature, computed over the raw
            // (unwrapped) file signature followed by the trusted comment text itself.
            $trustedCommentValid = sodium_crypto_sign_verify_detached(
                $signature['globalSignature'],
                $signature['signature'].$signature['trustedComment'],
                $publicKey['publicKey'],
            );
        } catch (SodiumException $exception) {
            return UpdatePackageVerificationResult::failure([
                'Minisign signature could not be verified: '.$exception->getMessage(),
            ], $details);
        }

        if (!$fileSignatureValid || !$trustedCommentValid) {
            return UpdatePackageVerificationResult::failure([
                'Minisign signature does not match the update package.',
            ], $details);
        }

        return UpdatePackageVerificationResult::success($details + [
            'signature_verified' => true,
            'signature_prehashed' => self::ALGORITHM_PREHASHED === $signature['algorithm'],
        ]);
    }

    /**
     * @return array{keyId: string, publicKey: string}
     */
    private function decodePublicKey(string $trustedPublicKey): array
    {
        $blob = base64_decode(trim($trustedPublicKey), true);

        if (false === $blob || self::PUBLIC_KEY_BLOB_BYTES !== \strlen($blob)) {
            throw new InvalidArgumentException('The trusted Minisign public key is not valid.');
        }

        if (self::ALGORITHM_LEGACY !== substr($blob, 0, 2)) {
            throw new InvalidArgumentException('The trusted Minisign public key uses an unsupported algorithm.');
        }

        return [
            'keyId' => substr($blob, 2, self::KEY_ID_BYTES),
            'publicKey' => substr($blob, 2 + self::KEY_ID_BYTES, self::PUBLIC_KEY_BYTES),
        ];
    }

    /**
     * @return array{algorithm: string, keyId: string, signature: string, trustedComment: string, globalSignature: string}
     */
    private function parseSignatureFile(string $signaturePath): array
    {
        $contents = file_get_contents($signaturePath);
        $lines = false === $contents ? false : preg_split('/\r\n|\r|\n/', $contents);

        if (false === $lines || \count($lines) < 4) {
            throw new InvalidArgumentException('The Minisign signature file does not have the expected format.');
        }

        [$untrustedCommentLine, $signatureLine, $trustedCommentLine, $globalSignatureLine] = $lines;

        if (!str_starts_with($untrustedCommentLine, self::UNTRUSTED_COMMENT_PREFIX)) {
            throw new InvalidArgumentException('The Minisign signature file is missing its untrusted comment line.');
        }

        if (!str_starts_with($trustedCommentLine, self::TRUSTED_COMMENT_PREFIX)) {
            throw new InvalidArgumentException('The Minisign signature file is missing its trusted comment line.');
        }

        $signatureBlob = base64_decode(trim($signatureLine), true);

        if (false === $signatureBlob || self::SIGNATURE_BLOB_BYTES !== \strlen($signatureBlob)) {
            throw new InvalidArgumentException('The Minisign signature block is not valid.');
        }

        $algorithm = substr($signatureBlob, 0, 2);

        if (!\in_array($algorithm, [self::ALGORITHM_LEGACY, self::ALGORITHM_PREHASHED], true)) {
            throw new InvalidArgumentException('The Minisign signature file uses an unsupported algorithm.');
        }

        $globalSignature = base64_decode(trim($globalSignatureLine), true);

        if (false === $globalSignature || self::SIGNATURE_BYTES !== \strlen($globalSignature)) {
            throw new InvalidArgumentException('The Minisign trusted comment signature is not valid.');
        }

        return [
            'algorithm' => $algorithm,
            'keyId' => substr($signatureBlob, 2, self::KEY_ID_BYTES),
            'signature' => substr($signatureBlob, 2 + self::KEY_ID_BYTES, self::SIGNATURE_BYTES),
            'trustedComment' => substr($trustedCommentLine, \strlen(self::TRUSTED_COMMENT_PREFIX)),
            'globalSignature' => $globalSignature,
        ];
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);

        if (false === $contents) {
            throw new RuntimeException('Unable to read the update package: '.$path);
        }

        return $contents;
    }

    /**
     * Streams the file through BLAKE2b-512 instead of loading it whole, since the
     * prehashed Minisign mode only ever signs this 64-byte digest.
     */
    private function hashFile(string $path): string
    {
        $handle = fopen($path, 'rb');

        if (false === $handle) {
            throw new RuntimeException('Unable to read the update package: '.$path);
        }

        try {
            $state = sodium_crypto_generichash_init('', self::PREHASH_BYTES);

            while (!feof($handle)) {
                $chunk = fread($handle, self::STREAM_CHUNK_BYTES);

                if (false === $chunk) {
                    throw new RuntimeException('Unable to read the update package: '.$path);
                }

                sodium_crypto_generichash_update($state, $chunk);
            }

            return sodium_crypto_generichash_final($state, self::PREHASH_BYTES);
        } finally {
            fclose($handle);
        }
    }

    private function formatKeyId(string $rawKeyId): string
    {
        return strtoupper(bin2hex(strrev($rawKeyId)));
    }
}
