<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\Welfare\WelfareReportingService;
use JsonException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const JSON_THROW_ON_ERROR;

/**
 * The multipart upload and authenticated binary download require a dedicated controller.
 * Other API operations delegate to one central confidentiality/authorization service.
 * No entity, user identifier or ciphertext is serialized automatically.
 */
#[AsController]
#[Route('/api/welfare')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final readonly class WelfareReportingController
{
    public function __construct(
        private WelfareReportingService $reporting
    ) {}

    #[Route('', name: 'welfare_my_reports', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $context = $this->reporting->currentContext();

        return $this->json([
            'mine' => $this->reporting->myReports($context['user'], $context['urlId']),
            'canInvestigate' => $this->reporting->isInvestigator(),
            'minimumDistinctReporters' => $this->reporting->minimumDistinctReporters(),
            'attachmentsEnabled' => $this->reporting->attachmentsEnabled(),
            'notificationEnabled' => $this->reporting->notificationsEnabled(),
        ]);
    }

    /**
     * Public navigation metadata for directly authenticated users only.
     */
    #[Route('/availability', name: 'welfare_availability', methods: ['GET'])]
    public function availability(): JsonResponse
    {
        $enabled = $this->reporting->isAvailable();

        return $this->json([
            'enabled' => $enabled,
            'canInvestigate' => $enabled && $this->reporting->isInvestigator(),
        ]);
    }

    #[Route('/targets', name: 'welfare_targets', methods: ['GET'])]
    public function targets(Request $request): JsonResponse
    {
        $context = $this->reporting->currentContext();

        return $this->json([
            'items' => $this->reporting->searchTargets(
                (string) $request->query->get('q', ''),
                $context['urlId'],
                (int) $context['user']->getId(),
            ),
        ]);
    }

    #[Route('/investigations', name: 'welfare_investigations', methods: ['GET'])]
    public function investigations(): JsonResponse
    {
        $context = $this->reporting->currentContext();

        return $this->json(['items' => $this->reporting->investigationTargets($context['urlId'])]);
    }

    #[Route('/investigations/{targetId}', name: 'welfare_investigation_reports', requirements: ['targetId' => '\d+'], methods: ['GET'])]
    public function investigationReports(int $targetId): JsonResponse
    {
        $context = $this->reporting->currentContext();

        return $this->json(['items' => $this->reporting->reportsForTarget($targetId, $context['urlId'])]);
    }

    #[Route('', name: 'welfare_create_report', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $context = $this->reporting->currentContext();
        $reference = $this->reporting->create(
            $context['user'],
            $context['urlId'],
            $request->request->getInt('targetUserId'),
            trim((string) $request->request->get('subject', '')),
            trim((string) $request->request->get('body', '')),
            $request->request->getBoolean('shareContact'),
            $this->files($request),
        );

        return $this->json(['reference' => $reference], Response::HTTP_CREATED);
    }

    #[Route('/{reference}', name: 'welfare_report_detail', requirements: ['reference' => '[a-f0-9]{32}'], methods: ['GET'])]
    public function detail(string $reference): JsonResponse
    {
        $context = $this->reporting->currentContext();

        return $this->json([
            ...$this->reporting->detail($reference, $context['user'], $context['urlId']),
            'attachmentsEnabled' => $this->reporting->attachmentsEnabled(),
        ]);
    }

    #[Route('/{reference}/messages', name: 'welfare_send_message', requirements: ['reference' => '[a-f0-9]{32}'], methods: ['POST'])]
    public function sendMessage(string $reference, Request $request): JsonResponse
    {
        $context = $this->reporting->currentContext();
        $this->reporting->reply(
            $reference,
            $context['user'],
            $context['urlId'],
            trim((string) $request->request->get('body', '')),
            $this->files($request),
        );

        return $this->json(['saved' => true], Response::HTTP_CREATED);
    }

    #[Route('/{reference}/contact', name: 'welfare_update_contact', requirements: ['reference' => '[a-f0-9]{32}'], methods: ['POST'])]
    public function updateContact(string $reference, Request $request): JsonResponse
    {
        $context = $this->reporting->currentContext();

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }
        if (!\is_array($payload) || !\is_bool($payload['shareContact'] ?? null)) {
            throw new BadRequestHttpException('The contact consent value is invalid.');
        }
        $this->reporting->updateContactConsent(
            $reference,
            $context['user'],
            $context['urlId'],
            $payload['shareContact'],
        );

        return $this->json(['saved' => true]);
    }

    #[Route('/{reference}/attachments/{id}', name: 'welfare_download_evidence', requirements: ['reference' => '[a-f0-9]{32}', 'id' => '\d+'], methods: ['GET'])]
    public function download(string $reference, int $id): Response
    {
        $context = $this->reporting->currentContext();
        $file = $this->reporting->download($id, $reference, $context['user'], $context['urlId']);
        $response = new Response($file['contents']);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $file['filename'],
            'confidential-evidence',
        ));
        $response->headers->set('Content-Security-Policy', "default-src 'none'; sandbox");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    /**
     * @return list<UploadedFile>
     */
    private function files(Request $request): array
    {
        $files = [];
        foreach ($request->files->all('attachments') as $file) {
            if (!$file instanceof UploadedFile) {
                throw new BadRequestHttpException('An invalid evidence attachment was submitted.');
            }
            $files[] = $file;
        }

        return $files;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function json(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = new JsonResponse($data, $status);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
