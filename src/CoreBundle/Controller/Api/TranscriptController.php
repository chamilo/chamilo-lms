<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Transcript;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\TranscriptRepository;
use Chamilo\CoreBundle\Security\Authorization\TranscriptAccessChecker;
use Chamilo\CoreBundle\Service\Gradebook\TranscriptBuilder;
use Chamilo\CoreBundle\Service\Gradebook\TranscriptIssuer;
use Chamilo\CoreBundle\Service\Gradebook\TranscriptPdfRenderer;
use Chamilo\CoreBundle\Service\Gradebook\TranscriptSignatureImage;
use Chamilo\CoreBundle\Settings\SettingsManager;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const DATE_ATOM;

/**
 * Transcript of a user: its unofficial version, computed on the fly for the user and the people
 * who manage them, and the official versions those managers issue, sign and can revoke.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/api/transcripts')]
final class TranscriptController
{
    public function __construct(
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly UserRepository $userRepository,
        private readonly TranscriptRepository $transcriptRepository,
        private readonly TranscriptAccessChecker $accessChecker,
        private readonly TranscriptBuilder $builder,
        private readonly TranscriptIssuer $issuer,
        private readonly TranscriptPdfRenderer $pdfRenderer,
        private readonly TranscriptSignatureImage $signatureImage,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    #[Route('/overview', name: 'api_transcript_overview', methods: ['GET'])]
    public function overview(Request $request): JsonResponse
    {
        $this->assertEnabled();
        $actor = $this->getActor();
        $url = $this->getAccessUrl();
        $target = $request->query->has('userId') ? $this->getUser($request->query->getInt('userId')) : $actor;
        if (!$this->accessChecker->canView($actor, $target, $url)) {
            throw new AccessDeniedHttpException('You are not allowed to see this transcript.');
        }

        $canIssue = $this->accessChecker->canIssue($actor, $target, $url);
        $canManageSignature = $this->accessChecker->canManageSignature();

        return new JsonResponse([
            'user' => ['id' => $target->getId(), 'fullName' => $target->getFullName()],
            'transcript' => $this->builder->build($target, $url),
            'unofficialPdfUrl' => $this->urlGenerator->generate('api_transcript_unofficial_pdf', ['userId' => $target->getId()]),
            'canIssue' => $canIssue,
            'officialTranscripts' => array_map(
                fn (Transcript $transcript): array => $this->serializeTranscript($transcript, $canIssue),
                $this->transcriptRepository->findByUserInUrl($target, $url),
            ),
            'canManageSignature' => $canManageSignature,
            'signatureImage' => $canManageSignature ? $this->signatureImage->getDataUri($url) : null,
        ]);
    }

    #[Route('/users/{userId}/unofficial.pdf', name: 'api_transcript_unofficial_pdf', requirements: ['userId' => '\d+'], methods: ['GET'])]
    public function unofficialPdf(int $userId): Response
    {
        $this->assertEnabled();
        $url = $this->getAccessUrl();
        $target = $this->getUser($userId);
        if (!$this->accessChecker->canView($this->getActor(), $target, $url)) {
            throw new AccessDeniedHttpException('You are not allowed to see this transcript.');
        }

        return $this->createPdfResponse($this->pdfRenderer->renderUnofficial($this->builder->build($target, $url)), 'unofficial-transcript.pdf');
    }

    #[Route('', name: 'api_transcript_issue', methods: ['POST'])]
    public function issue(Request $request): JsonResponse
    {
        $this->assertEnabled();
        $actor = $this->getActor();
        $url = $this->getAccessUrl();
        $data = json_decode($request->getContent(), true);
        $target = $this->getUser(\is_array($data) ? (int) ($data['userId'] ?? 0) : 0);
        if (!$this->accessChecker->canIssue($actor, $target, $url)) {
            throw new AccessDeniedHttpException('You are not allowed to issue the official transcript of this user.');
        }

        $transcript = $this->issuer->issue($target, $actor, $url, $request->getSchemeAndHttpHost());

        return new JsonResponse($this->serializeTranscript($transcript, true), Response::HTTP_CREATED);
    }

    #[Route('/{id}/revoke', name: 'api_transcript_revoke', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function revoke(int $id): JsonResponse
    {
        $this->assertEnabled();
        $transcript = $this->getTranscript($this->transcriptRepository->find($id));
        if (!$this->accessChecker->canIssue($this->getActor(), $transcript->getUser(), $transcript->getUrl())) {
            throw new AccessDeniedHttpException('You are not allowed to revoke this transcript.');
        }

        $this->issuer->revoke($transcript);

        return new JsonResponse($this->serializeTranscript($transcript, true));
    }

    #[Route('/{token}/official.pdf', name: 'api_transcript_official_pdf', requirements: ['token' => '[0-9a-f]{64}'], methods: ['GET'])]
    public function officialPdf(string $token): Response
    {
        $transcript = $this->getTranscript($this->transcriptRepository->findOneByToken($token));
        if (!$this->accessChecker->canView($this->getActor(), $transcript->getUser(), $transcript->getUrl())) {
            throw new AccessDeniedHttpException('You are not allowed to see this transcript.');
        }

        return $this->createPdfResponse(
            $this->pdfRenderer->renderOfficial($transcript, $this->getVerificationUrl($transcript, UrlGeneratorInterface::ABSOLUTE_URL)),
            'official-transcript.pdf',
        );
    }

    #[Route('/signature-image', name: 'api_transcript_signature_image_upload', methods: ['POST'])]
    public function uploadSignatureImage(Request $request): JsonResponse
    {
        $this->assertCanManageSignature();
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('A signature image is required.');
        }

        try {
            $this->signatureImage->save($this->getAccessUrl(), $file);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        return new JsonResponse(['signatureImage' => $this->signatureImage->getDataUri($this->getAccessUrl())]);
    }

    #[Route('/signature-image', name: 'api_transcript_signature_image_delete', methods: ['DELETE'])]
    public function deleteSignatureImage(): JsonResponse
    {
        $this->assertCanManageSignature();
        $this->signatureImage->delete($this->getAccessUrl());

        return new JsonResponse(['signatureImage' => null]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTranscript(Transcript $transcript, bool $canRevoke): array
    {
        return [
            'id' => $transcript->getId(),
            'issuedAt' => $transcript->getIssuedAt()->format(DATE_ATOM),
            'issuedBy' => $transcript->getIssuedBy()?->getFullName(),
            'revokedAt' => $transcript->getRevokedAt()?->format(DATE_ATOM),
            'status' => $this->issuer->getStatus($transcript),
            'canRevoke' => $canRevoke && !$transcript->isRevoked(),
            'verificationUrl' => $this->getVerificationUrl($transcript, UrlGeneratorInterface::ABSOLUTE_PATH),
            'downloadUrl' => $this->urlGenerator->generate('api_transcript_official_pdf', ['token' => $transcript->getToken()]),
        ];
    }

    private function getVerificationUrl(Transcript $transcript, int $referenceType): string
    {
        return $this->urlGenerator->generate('transcript_verify', ['token' => $transcript->getToken()], $referenceType);
    }

    private function createPdfResponse(string $pdf, string $fileName): Response
    {
        $response = new Response($pdf, Response::HTTP_OK, ['Content-Type' => 'application/pdf']);
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName),
        );

        return $response;
    }

    private function assertEnabled(): void
    {
        if ('true' !== $this->settingsManager->getSetting('certificate.transcript_enabled', true)) {
            throw new NotFoundHttpException('Transcripts are not enabled.');
        }
    }

    private function assertCanManageSignature(): void
    {
        $this->assertEnabled();
        if (!$this->accessChecker->canManageSignature()) {
            throw new AccessDeniedHttpException('Only administrators can change the transcript signature.');
        }
    }

    private function getActor(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function getUser(int $userId): User
    {
        $user = $userId > 0 ? $this->userRepository->find($userId) : null;
        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found.');
        }

        return $user;
    }

    private function getTranscript(?Transcript $transcript): Transcript
    {
        if (null === $transcript || $transcript->getUrl()->getId() !== $this->getAccessUrl()->getId()) {
            throw new NotFoundHttpException('Transcript not found.');
        }

        return $transcript;
    }

    private function getAccessUrl(): AccessUrl
    {
        return $this->accessUrlHelper->getCurrent() ?? throw new AccessDeniedHttpException('A valid access URL is required.');
    }
}
