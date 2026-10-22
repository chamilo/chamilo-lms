<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Transcript;
use Chamilo\CoreBundle\Repository\TranscriptRepository;
use Chamilo\CoreBundle\Service\Gradebook\TranscriptIssuer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use const DATE_ATOM;

/**
 * Public page where anyone given the verification link of an official transcript checks that it
 * was issued by this institution, has not been altered and is not revoked. The transcript content
 * is only shown while it is valid.
 */
final class TranscriptVerificationController extends AbstractController
{
    private const array LABELS = [
        TranscriptIssuer::STATUS_VALID => 'Valid',
        TranscriptIssuer::STATUS_REVOKED => 'Revoked',
        TranscriptIssuer::STATUS_INVALID_SIGNATURE => 'Invalid signature',
        'not_found' => 'Transcript not found',
    ];

    private const array TONES = [
        TranscriptIssuer::STATUS_VALID => 'valid',
        TranscriptIssuer::STATUS_REVOKED => 'danger',
        TranscriptIssuer::STATUS_INVALID_SIGNATURE => 'danger',
        'not_found' => 'neutral',
    ];

    public function __construct(
        private readonly TranscriptRepository $transcriptRepository,
        private readonly TranscriptIssuer $issuer,
    ) {}

    #[Route('/transcripts/verify/{token}', name: 'transcript_verify', requirements: ['token' => '[0-9a-f]{64}'], methods: ['GET'])]
    public function verify(string $token): Response
    {
        $verification = $this->getVerification($token);
        $status = $verification['status'];

        $response = $this->render('@ChamiloCore/Transcript/verification.html.twig', [
            'statusLabel' => self::LABELS[$status],
            'statusTone' => self::TONES[$status],
            'valid' => $verification['valid'],
            'transcript' => $verification['transcript'] ?? null,
            'jsonUrl' => $this->generateUrl('transcript_verify_json', ['token' => $token]),
        ]);
        $response->setStatusCode('not_found' === $status ? Response::HTTP_NOT_FOUND : Response::HTTP_OK);
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'");

        return $response;
    }

    #[Route('/transcripts/verify/{token}.json', name: 'transcript_verify_json', requirements: ['token' => '[0-9a-f]{64}'], methods: ['GET'])]
    public function verifyJson(string $token): JsonResponse
    {
        $verification = $this->getVerification($token);

        $response = new JsonResponse($verification, 'not_found' === $verification['status'] ? Response::HTTP_NOT_FOUND : Response::HTTP_OK);
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function getVerification(string $token): array
    {
        $transcript = $this->transcriptRepository->findOneByToken($token);
        if (!$transcript instanceof Transcript) {
            return ['status' => 'not_found', 'valid' => false];
        }

        $status = $this->issuer->getStatus($transcript);
        $verification = [
            'status' => $status,
            'valid' => TranscriptIssuer::STATUS_VALID === $status,
            'signatureValid' => TranscriptIssuer::STATUS_INVALID_SIGNATURE !== $status,
            'issuedAt' => $transcript->getIssuedAt()->format(DATE_ATOM),
            'revokedAt' => $transcript->getRevokedAt()?->format(DATE_ATOM),
        ];

        // The signed data is only disclosed while it is valid, so that a revoked transcript stops
        // attesting anything about its holder.
        if ($verification['valid']) {
            $verification['transcript'] = $transcript->getPayload();
            $verification['payloadJson'] = $transcript->getPayloadJson();
            $verification['signature'] = $transcript->getSignature();
            $verification['publicKeyHex'] = $transcript->getPublicKeyHex();
        }

        return $verification;
    }
}
