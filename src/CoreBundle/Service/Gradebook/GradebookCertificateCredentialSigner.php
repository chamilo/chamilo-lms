<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookCertificateCredential;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use RuntimeException;
use Throwable;

use const DATE_ATOM;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Signs certificate credentials with the institution issuer key (see IssuerSigningKey).
 *
 * Chamilo Certificate Credential v1 is NOT advertised as W3C VC/Data Integrity.
 */
final readonly class GradebookCertificateCredentialSigner
{
    public function __construct(
        private IssuerSigningKey $issuerKey,
        private GradebookCertificateRepository $certificateRepository,
    ) {}

    public function issue(GradebookCertificate $certificate, string $issuerUrl): GradebookCertificateCredential
    {
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
        $public = $this->issuerKey->publicKeyHex();
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
        $signed = $this->issuerKey->sign($json);

        return new GradebookCertificateCredential($certificate, $token, $json, $signed['signature'], $signed['publicKeyHex']);
    }

    public function validSignature(GradebookCertificateCredential $credential): bool
    {
        return $this->issuerKey->verify($credential->getPayloadJson(), $credential->getSignature(), $credential->getPublicKeyHex())
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
