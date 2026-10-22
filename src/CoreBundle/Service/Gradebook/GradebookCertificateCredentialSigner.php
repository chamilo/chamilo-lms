<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookCertificateCredential;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

use const DATE_ATOM;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const SODIUM_CRYPTO_SIGN_BYTES;
use const SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES;
use const SODIUM_CRYPTO_SIGN_SEEDBYTES;

/**
 * Ed25519 issuer key kept outside public/. A backup is mandatory; deleting it
 * loses the ability to issue with the same institution identity.
 *
 * Chamilo Certificate Credential v1 is NOT advertised as W3C VC/Data Integrity.
 */
final readonly class GradebookCertificateCredentialSigner
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private EntityManagerInterface $entityManager,
        private GradebookCertificateRepository $certificateRepository,
    ) {}

    /**
     * @return array{0:string,1:string} secret binary and hex public key
     */
    private function key(): array
    {
        if (!\extension_loaded('sodium')) {
            throw new RuntimeException('The sodium PHP extension is required.');
        }
        $file = $this->projectDir.'/var/certificate-issuer/ed25519.seed';
        if (!is_file($file)) {
            // Never silently switch issuer identity after credentials have been issued.
            $count = (int) $this->entityManager->getRepository(GradebookCertificateCredential::class)->count([]);
            if ($count > 0) {
                throw new RuntimeException('Issuer key is missing; restore its secure backup.');
            }
            $dir = \dirname($file);
            if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
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

    public function issue(GradebookCertificate $certificate, string $issuerUrl): GradebookCertificateCredential
    {
        [$secret, $public] = $this->key();
        $category = $certificate->getCategory();
        if (null === $category || !str_starts_with($issuerUrl, 'https://')) {
            throw new RuntimeException('A category and an HTTPS issuer URL are required.');
        }
        if (!$certificate->hasResourceNode()) {
            throw new RuntimeException('Migrate this legacy certificate to ResourceNode storage before signing.');
        }

        try {
            $sourceHtml = $this->certificateRepository->getResourceFileContent($certificate);
        } catch (Throwable $e) {
            throw new RuntimeException('Unable to read certificate HTML before signing.', 0, $e);
        }
        if ('' === trim($sourceHtml)) {
            throw new RuntimeException('Certificate HTML is empty.');
        }
        $token = bin2hex(random_bytes(32));
        $payload = [
            'format' => 'chamilo-certificate-credential-v1',
            'id' => $token,
            'issuer' => $issuerUrl,
            'publicKeyFingerprint' => hash('sha256', hex2bin($public)),
            'subject' => $certificate->getUser()->getFullName(),
            'course' => (string) $category->getCourse()->getTitle(),
            'courseCode' => (string) $category->getCourse()->getCode(),
            'certificateId' => $certificate->getId(),
            'categoryId' => $category->getId(),
            'score' => $certificate->getScoreCertificate(),
            'issuedAt' => $certificate->getCreatedAt()->format(DATE_ATOM),
            'expiresAt' => $certificate->getExpiryDate()?->format('Y-m-d'),
            'sourcePathFingerprint' => hash('sha256', (string) $certificate->getPathCertificate()),
            'sourceHtmlSha256' => hash('sha256', $sourceHtml),
        ];
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = base64_encode(sodium_crypto_sign_detached($json, $secret));
        sodium_memzero($secret);

        return new GradebookCertificateCredential($certificate, $token, $json, $signature, $public);
    }

    public function validSignature(GradebookCertificateCredential $credential): bool
    {
        if (!\extension_loaded('sodium')) {
            return false;
        }
        // Trust the institution key kept outside the document root, not an
        // arbitrary public key supplied alongside an attacker-signed payload.
        $issuerKeyFile = $this->projectDir.'/var/certificate-issuer/ed25519.seed';
        $issuerSeed = @file_get_contents($issuerKeyFile);
        if (!\is_string($issuerSeed) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $issuerSeed)) {
            return false;
        }
        $issuerPair = sodium_crypto_sign_seed_keypair(hex2bin($issuerSeed));
        $trustedKeyHex = bin2hex(sodium_crypto_sign_publickey($issuerPair));
        if (!hash_equals($trustedKeyHex, $credential->getPublicKeyHex())) {
            return false;
        }
        $key = hex2bin($credential->getPublicKeyHex());
        $signature = base64_decode($credential->getSignature(), true);
        if (false === $key || false === $signature
            || SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== \strlen($key)
            || SODIUM_CRYPTO_SIGN_BYTES !== \strlen($signature)) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($signature, $credential->getPayloadJson(), $key)
            && hash_equals($credential->getAnchorHash(), hash('sha256', $credential->getPayloadJson().'.'.$credential->getSignature()));
    }

    public function matchesCurrentSource(GradebookCertificateCredential $credential): bool
    {
        $certificate = $credential->getCertificate();
        if (!$certificate instanceof GradebookCertificate || !$certificate->hasResourceNode()) {
            return false;
        }
        $payload = json_decode($credential->getPayloadJson(), true);
        if (!\is_array($payload) || !\is_string($payload['sourceHtmlSha256'] ?? null)) {
            return false;
        }

        try {
            $html = $this->certificateRepository->getResourceFileContent($certificate);
        } catch (Throwable) {
            return false;
        }

        return hash_equals($payload['sourceHtmlSha256'], hash('sha256', $html));
    }

    public function anchorData(GradebookCertificateCredential $credential): string
    {
        // A human-recognizable marker followed by exactly 32 bytes of SHA-256.
        return '0x'.bin2hex('CHAMILO-CERT-V1|').$credential->getAnchorHash();
    }
}
