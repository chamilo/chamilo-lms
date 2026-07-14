<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\Ticket\TicketWorkflowService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
final readonly class TicketWorkflowController
{
    public function __construct(
        private TicketWorkflowService $workflowService,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    #[Route('/api/ticket/create', name: 'ticket_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $ticket = $this->workflowService->createTicket(
            $this->getAuthenticatedUser(),
            $data,
            $this->getUploadedFiles($request),
        );

        return new JsonResponse([
            'id' => (int) $ticket->getId(),
            'code' => $ticket->getCode(),
            'message' => get_lang('Saved.'),
        ], Response::HTTP_CREATED);
    }

    #[Route(
        '/api/ticket/{id}/reply',
        name: 'ticket_reply',
        requirements: ['id' => '\\d+'],
        methods: ['POST'],
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function reply(int $id, Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $ticket = $this->workflowService->getTicketForCurrentAccessUrl($id);
        $message = $this->workflowService->replyToTicket(
            $ticket,
            $this->getAuthenticatedUser(),
            $data,
            $this->getUploadedFiles($request),
        );

        return new JsonResponse([
            'id' => (int) $message->getId(),
            'message' => get_lang('Saved.'),
        ]);
    }

    #[Route(
        '/api/ticket/{id}/subscribe',
        name: 'ticket_subscribe',
        requirements: ['id' => '\\d+'],
        methods: ['POST'],
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function subscribe(int $id, Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $ticket = $this->workflowService->getTicketForCurrentAccessUrl($id);
        $this->workflowService->subscribe($ticket, $this->getAuthenticatedUser());

        return new JsonResponse([
            'subscribed' => true,
            'message' => get_lang("You're now subscribed."),
        ]);
    }

    #[Route(
        '/api/ticket/{id}/unsubscribe',
        name: 'ticket_unsubscribe',
        requirements: ['id' => '\\d+'],
        methods: ['POST'],
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function unsubscribe(int $id, Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $ticket = $this->workflowService->getTicketForCurrentAccessUrl($id);
        $this->workflowService->unsubscribe($ticket, $this->getAuthenticatedUser());

        return new JsonResponse([
            'subscribed' => false,
            'message' => get_lang("You're now unsubscribed."),
        ]);
    }

    #[Route(
        '/api/ticket/{id}/close',
        name: 'ticket_close',
        requirements: ['id' => '\\d+'],
        methods: ['POST'],
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function close(int $id, Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $ticket = $this->workflowService->getTicketForCurrentAccessUrl($id);
        $this->workflowService->close($ticket, $this->getAuthenticatedUser());

        return new JsonResponse([
            'closed' => true,
            'message' => get_lang('Ticket closed'),
        ]);
    }

    #[Route('/api/ticket/user-options', name: 'ticket_user_options', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function userOptions(Request $request): JsonResponse
    {
        return new JsonResponse([
            'items' => $this->workflowService->searchAssignableUsers(
                (string) $request->query->get('query', ''),
            ),
        ]);
    }

    /** @return array<string, mixed> */
    private function getRequestData(Request $request): array
    {
        if (str_contains((string) $request->headers->get('Content-Type'), 'application/json')) {
            $decoded = json_decode($request->getContent(), true);
            if (!\is_array($decoded)) {
                throw new BadRequestHttpException('The request body must contain valid JSON.');
            }

            return $decoded;
        }

        return $request->request->all();
    }

    /** @return array<int, UploadedFile> */
    private function getUploadedFiles(Request $request): array
    {
        $files = [];
        $this->collectUploadedFiles($request->files->all('attachments'), $files);

        return $files;
    }

    /**
     * @param mixed $value
     * @param array<int, UploadedFile> $files
     */
    private function collectUploadedFiles(mixed $value, array &$files): void
    {
        if ($value instanceof UploadedFile) {
            $files[] = $value;

            return;
        }

        if (!\is_array($value)) {
            return;
        }

        foreach ($value as $item) {
            $this->collectUploadedFiles($item, $files);
        }
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new BadRequestHttpException('The authenticated user is required.');
        }

        return $user;
    }

    private function validateCsrfToken(string $value): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(TicketWorkflowService::CSRF_TOKEN_ID, $value))) {
            throw new BadRequestHttpException('Invalid security token. Please reload the page and try again.');
        }
    }
}
