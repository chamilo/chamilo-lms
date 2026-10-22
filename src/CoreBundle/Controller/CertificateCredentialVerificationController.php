<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\GradebookCertificateCredential;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateCredentialSigner;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use const DATE_ATOM;

/** Public verification is only accessible with a random 256-bit credential token. */
final class CertificateCredentialVerificationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GradebookCertificateCredentialSigner $signer,
    ) {}

    #[Route('/certificates/verify/{token}', name: 'certificate_credential_verify', requirements: ['token' => '[0-9a-f]{64}'], methods: ['GET'])]
    public function verify(string $token, Request $request): Response
    {
        $verification = $this->status($token);
        $status = (string) $verification['status'];
        $labels = [
            'valid' => 'Valid',
            'revoked' => 'Revoked',
            'consent_withdrawn' => 'Consent withdrawn',
            'invalid_signature' => 'Invalid signature',
            'source_changed_or_deleted' => 'Certificate changed or deleted',
            'expired' => 'Expired',
            'not_found' => 'Credential not found',
        ];
        $tones = [
            'valid' => 'valid',
            'revoked' => 'danger',
            'consent_withdrawn' => 'warning',
            'invalid_signature' => 'danger',
            'source_changed_or_deleted' => 'danger',
            'expired' => 'warning',
            'not_found' => 'neutral',
        ];

        // The explorer URL is only built from a validated on-chain transaction hash.
        $txHash = (string) ($verification['transactionHash'] ?? '');
        $explorerUrl = 1 === preg_match('/^0x[0-9a-f]{64}$/D', $txHash)
            ? 'https://sepolia.etherscan.io/tx/'.$txHash
            : null;

        [$backUrl, $backLabel] = $this->getBackNavigation($request);

        $body = $this->renderView('@ChamiloCore/Certificate/credential_verification.html.twig', [
            'statusLabel' => $labels[$status] ?? 'Unknown status',
            'statusTone' => $tones[$status] ?? 'neutral',
            'valid' => true === ($verification['valid'] ?? false),
            'credential' => $verification['credential'] ?? null,
            'signatureValid' => true === ($verification['signatureValid'] ?? false),
            'hasSignature' => \array_key_exists('signatureValid', $verification),
            'anchorStatus' => $verification['anchorStatus'] ?? null,
            'explorerUrl' => $explorerUrl,
            'jsonUrl' => '/certificates/verify/'.$token.'.json',
            'backUrl' => $backUrl,
            'backLabel' => $backLabel,
        ]);

        $response = new Response($body, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'");

        return $response;
    }

    /**
     * Offer a safe return link for users arriving from Chamilo certificate screens.
     * Direct visitors and external referrers always receive the public home link.
     *
     * @return array{string, string}
     */
    private function getBackNavigation(Request $request): array
    {
        $fallback = ['/', 'Back to Chamilo'];
        $referer = $request->headers->get('referer');
        if (null === $referer || '' === $referer) {
            return $fallback;
        }

        $origin = parse_url($request->getSchemeAndHttpHost());
        $source = parse_url($referer);
        if (!\is_array($origin) || !\is_array($source)
            || isset($source['user']) || isset($source['pass'])
            || strtolower((string) ($source['scheme'] ?? '')) !== strtolower((string) ($origin['scheme'] ?? ''))
            || 0 !== strcasecmp((string) ($source['host'] ?? ''), (string) ($origin['host'] ?? ''))
            || (int) ($source['port'] ?? 0) !== (int) ($origin['port'] ?? 0)
        ) {
            return $fallback;
        }

        $path = (string) ($source['path'] ?? '');
        if ('/my-certificates' === $path) {
            return ['/my-certificates', 'Back to my certificates'];
        }

        if (1 !== preg_match('~^/resources/gradebook/[0-9]+/certificates$~D', $path)) {
            return $fallback;
        }

        // Preserve only known numeric Gradebook context parameters.
        $originalQuery = [];
        parse_str((string) ($source['query'] ?? ''), $originalQuery);
        $query = [];
        foreach (['cid', 'sid', 'gid'] as $name) {
            $value = $originalQuery[$name] ?? null;
            if (\is_string($value) && 1 === preg_match('/^[0-9]+$/D', $value)) {
                $query[$name] = $value;
            }
        }

        $backUrl = $path.([] !== $query ? '?'.http_build_query($query) : '');

        return [$backUrl, 'Back to certificates'];
    }

    #[Route('/certificates/verify/{token}.json', name: 'certificate_credential_verify_json', requirements: ['token' => '[0-9a-f]{64}'], methods: ['GET'])]
    public function downloadJson(string $token): JsonResponse
    {
        $response = new JsonResponse($this->status($token));
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }

    /**
     * @return array<string,mixed>
     */
    private function status(string $token): array
    {
        $credential = $this->entityManager->getRepository(GradebookCertificateCredential::class)->findOneBy(['token' => $token]);
        if (!$credential instanceof GradebookCertificateCredential) {
            return ['status' => 'not_found', 'valid' => false];
        }
        $certificate = $credential->getCertificate();
        $payload = json_decode($credential->getPayloadJson(), true);
        $signed = $this->signer->validSignature($credential);
        $consented = null !== $certificate && null !== $certificate->getUserPublicationApprovalDate()
            && 'sepolia-hash-only-v1' === $certificate->getUserPublicationConsentScope();
        $expired = \is_array($payload) && isset($payload['expiresAt']) && \is_string($payload['expiresAt'])
            && '' !== $payload['expiresAt'] && $payload['expiresAt'] < (new DateTimeImmutable('today', new DateTimeZone('UTC')))->format('Y-m-d');
        $unchanged = null !== $certificate && \is_array($payload)
            && (int) ($payload['certificateId'] ?? -1) === $certificate->getId()
            && abs((float) ($payload['score'] ?? -1) - $certificate->getScoreCertificate()) < 0.00001
            && (string) ($payload['issuedAt'] ?? '') === $certificate->getCreatedAt()->format(DATE_ATOM)
            && ($payload['expiresAt'] ?? null) === $certificate->getExpiryDate()?->format('Y-m-d')
            && hash_equals((string) ($payload['sourcePathFingerprint'] ?? ''), hash('sha256', (string) $certificate->getPathCertificate()));
        $unchanged = $unchanged && $this->signer->matchesCurrentSource($credential);
        $valid = $signed && $consented && !$expired && $unchanged && null === $credential->getRevokedAt();
        $status = match (true) {
            !$signed => 'invalid_signature',
            null !== $credential->getRevokedAt() => 'revoked',
            !$consented => 'consent_withdrawn',
            !$unchanged => 'source_changed_or_deleted',
            $expired => 'expired',
            default => 'valid',
        };
        $anchored = null !== $credential->getTxHash();
        $result = [
            'status' => $status,
            'valid' => $valid,
            'format' => 'chamilo-certificate-credential-v1',
            'signatureValid' => $signed,
            'anchorStatus' => $anchored ? 'previously_confirmed_on_sepolia' : 'not_anchored',
            'chainId' => $credential->getChainId(),
            'transactionHash' => $credential->getTxHash(),
            'fingerprint' => $credential->getAnchorHash(),
        ];
        if ($valid) {
            $result['credential'] = $payload;
            $result['payloadJson'] = $credential->getPayloadJson();
            $result['signature'] = $credential->getSignature();
            $result['publicKeyHex'] = $credential->getPublicKeyHex();
        }

        return $result;
    }
}
