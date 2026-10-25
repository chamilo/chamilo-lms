<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\GradebookCertificateCredential;
use Chamilo\CoreBundle\Entity\Transcript;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use const SODIUM_CRYPTO_SIGN_BYTES;
use const SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES;
use const SODIUM_CRYPTO_SIGN_SEEDBYTES;

/**
 * Ed25519 issuer key kept outside public/, shared by the signed certificate credentials and the
 * official transcripts. A backup is mandatory; deleting it loses the ability to issue with the
 * same institution identity.
 */
final readonly class IssuerSigningKey
{
    public function __construct(
        #[Autowire('%chamilo.certificate_issuer_dir%')]
        private string $keyDir,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{signature: string, publicKeyHex: string} base64 detached signature and hex public key
     */
    public function sign(string $payload): array
    {
        [$secret, $public] = $this->keyPair();
        $signature = base64_encode(sodium_crypto_sign_detached($payload, $secret));
        sodium_memzero($secret);

        return ['signature' => $signature, 'publicKeyHex' => $public];
    }

    /**
     * The public key of the institution, created on first use like sign() does.
     */
    public function publicKeyHex(): string
    {
        [$secret, $public] = $this->keyPair();
        sodium_memzero($secret);

        return $public;
    }

    public function verify(string $payload, string $signatureBase64, string $publicKeyHex): bool
    {
        if (!\extension_loaded('sodium')) {
            return false;
        }
        // Trust the institution key kept outside the document root, not an
        // arbitrary public key supplied alongside an attacker-signed payload.
        $trustedKeyHex = $this->trustedPublicKeyHex();
        if (null === $trustedKeyHex || !hash_equals($trustedKeyHex, $publicKeyHex)) {
            return false;
        }
        $key = hex2bin($publicKeyHex);
        $signature = base64_decode($signatureBase64, true);
        if (false === $key || false === $signature
            || SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== \strlen($key)
            || SODIUM_CRYPTO_SIGN_BYTES !== \strlen($signature)) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($signature, $payload, $key);
    }

    private function trustedPublicKeyHex(): ?string
    {
        $seed = @file_get_contents($this->keyFile());
        if (!\is_string($seed) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $seed)) {
            return null;
        }

        return bin2hex(sodium_crypto_sign_publickey(sodium_crypto_sign_seed_keypair(hex2bin($seed))));
    }

    /**
     * @return array{0:string,1:string} secret binary and hex public key
     */
    private function keyPair(): array
    {
        if (!\extension_loaded('sodium')) {
            throw new RuntimeException('The sodium PHP extension is required.');
        }
        $file = $this->keyFile();
        if (!is_file($file)) {
            // Never silently switch issuer identity after documents have been signed.
            if ($this->hasSignedDocuments()) {
                throw new RuntimeException('Issuer key is missing; restore its secure backup.');
            }
            if (!is_dir($this->keyDir) && !mkdir($this->keyDir, 0700, true) && !is_dir($this->keyDir)) {
                throw new RuntimeException('Cannot create the issuer key directory.');
            }
            $seed = random_bytes(SODIUM_CRYPTO_SIGN_SEEDBYTES);
            $handle = @fopen($file, 'x');
            if (false !== $handle) {
                try {
                    @chmod($file, 0600);
                    if (false === fwrite($handle, bin2hex($seed))) {
                        throw new RuntimeException('Cannot save issuer key.');
                    }
                    fflush($handle);
                } finally {
                    fclose($handle);
                }
            }
        }
        $raw = @file_get_contents($file);
        if (!\is_string($raw) || 1 !== preg_match('/^[0-9a-f]{64}$/D', $raw)) {
            throw new RuntimeException('Issuer key missing or invalid.');
        }
        $keypair = sodium_crypto_sign_seed_keypair(hex2bin($raw));

        return [sodium_crypto_sign_secretkey($keypair), bin2hex(sodium_crypto_sign_publickey($keypair))];
    }

    private function hasSignedDocuments(): bool
    {
        return $this->entityManager->getRepository(GradebookCertificateCredential::class)->count([]) > 0
            || $this->entityManager->getRepository(Transcript::class)->count([]) > 0;
    }

    private function keyFile(): string
    {
        return $this->keyDir.'/ed25519.seed';
    }
}
