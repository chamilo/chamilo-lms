<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\Ticket\TicketAdminService;
use Chamilo\CoreBundle\Service\Ticket\TicketWorkflowService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
#[Route('/api/ticket/admin')]
#[IsGranted('ROLE_ADMIN')]
final readonly class TicketAdminController
{
    public function __construct(
        private TicketAdminService $adminService,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    #[Route('/configuration', name: 'ticket_admin_configuration', methods: ['GET'])]
    public function configuration(Request $request): JsonResponse
    {
        $data = $this->adminService->getConfiguration($request->query->getInt('projectId'));
        $data['csrfToken'] = $this->csrfTokenManager->getToken(TicketWorkflowService::CSRF_TOKEN_ID)->getValue();

        return new JsonResponse($data);
    }

    #[Route('/projects', name: 'ticket_admin_project_create', methods: ['POST'])]
    public function createProject(Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $project = $this->adminService->createProject($this->getAuthenticatedUser(), $data);

        return new JsonResponse(
            ['id' => (int) $project->getId(), 'message' => get_lang('Added')],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/projects/{id}', name: 'ticket_admin_project_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function updateProject(int $id, Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $project = $this->adminService->updateProject($id, $this->getAuthenticatedUser(), $data);

        return new JsonResponse(['id' => (int) $project->getId(), 'message' => get_lang('Update successful')]);
    }

    #[Route('/projects/{id}', name: 'ticket_admin_project_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteProject(int $id, Request $request): JsonResponse
    {
        $this->validateCsrfToken((string) $request->headers->get('X-CSRF-TOKEN', ''));
        $this->adminService->deleteProject($id);

        return new JsonResponse(['message' => get_lang('Deleted')]);
    }

    #[Route(
        '/projects/{projectId}/categories',
        name: 'ticket_admin_category_create',
        requirements: ['projectId' => '\d+'],
        methods: ['POST'],
    )]
    public function createCategory(int $projectId, Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $category = $this->adminService->createCategory($projectId, $this->getAuthenticatedUser(), $data);

        return new JsonResponse(
            ['id' => (int) $category->getId(), 'message' => get_lang('Added')],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/categories/{id}', name: 'ticket_admin_category_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function updateCategory(int $id, Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $category = $this->adminService->updateCategory($id, $this->getAuthenticatedUser(), $data);

        return new JsonResponse(['id' => (int) $category->getId(), 'message' => get_lang('Update successful')]);
    }

    #[Route(
        '/categories/{id}',
        name: 'ticket_admin_category_delete',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'],
    )]
    public function deleteCategory(int $id, Request $request): JsonResponse
    {
        $this->validateCsrfToken((string) $request->headers->get('X-CSRF-TOKEN', ''));
        $this->adminService->deleteCategory($id);

        return new JsonResponse(['message' => get_lang('Deleted')]);
    }

    #[Route(
        '/categories/{id}/users',
        name: 'ticket_admin_category_users',
        requirements: ['id' => '\d+'],
        methods: ['PUT'],
    )]
    public function updateCategoryUsers(int $id, Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $rawUserIds = $data['userIds'] ?? [];
        if (!\is_array($rawUserIds)) {
            throw new BadRequestHttpException('The selected users are invalid.');
        }
        $this->adminService->replaceCategoryUsers($id, array_map('intval', $rawUserIds));

        return new JsonResponse(['message' => get_lang('Update successful')]);
    }

    #[Route('/statuses', name: 'ticket_admin_status_create', methods: ['POST'])]
    public function createStatus(Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $status = $this->adminService->createStatus($data);

        return new JsonResponse(
            ['id' => (int) $status->getId(), 'message' => get_lang('Added')],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/statuses/{id}', name: 'ticket_admin_status_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $status = $this->adminService->updateStatus($id, $data);

        return new JsonResponse(['id' => (int) $status->getId(), 'message' => get_lang('Update successful')]);
    }

    #[Route('/statuses/{id}', name: 'ticket_admin_status_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteStatus(int $id, Request $request): JsonResponse
    {
        $this->validateCsrfToken((string) $request->headers->get('X-CSRF-TOKEN', ''));
        $this->adminService->deleteStatus($id);

        return new JsonResponse(['message' => get_lang('Deleted')]);
    }

    #[Route('/priorities', name: 'ticket_admin_priority_create', methods: ['POST'])]
    public function createPriority(Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $priority = $this->adminService->createPriority($this->getAuthenticatedUser(), $data);

        return new JsonResponse(
            ['id' => (int) $priority->getId(), 'message' => get_lang('Added')],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/priorities/{id}', name: 'ticket_admin_priority_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function updatePriority(int $id, Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $priority = $this->adminService->updatePriority($id, $this->getAuthenticatedUser(), $data);

        return new JsonResponse(['id' => (int) $priority->getId(), 'message' => get_lang('Update successful')]);
    }

    #[Route(
        '/priorities/{id}',
        name: 'ticket_admin_priority_delete',
        requirements: ['id' => '\d+'],
        methods: ['DELETE'],
    )]
    public function deletePriority(int $id, Request $request): JsonResponse
    {
        $this->validateCsrfToken((string) $request->headers->get('X-CSRF-TOKEN', ''));
        $this->adminService->deletePriority($id);

        return new JsonResponse(['message' => get_lang('Deleted')]);
    }

    #[Route('/close-old', name: 'ticket_admin_close_old', methods: ['POST'])]
    public function closeOld(Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken((string) ($data['csrfToken'] ?? ''));
        $count = $this->adminService->closeOldTickets($this->getAuthenticatedUser());

        return new JsonResponse(['count' => $count, 'message' => get_lang('Update successful')]);
    }

    #[Route('/export', name: 'ticket_admin_export', methods: ['GET'])]
    public function export(Request $request): BinaryFileResponse
    {
        $rows = $this->adminService->getExportRows($request->query->all());
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $spreadsheet->getActiveSheet()->setTitle('Tickets');
        $temporaryFile = tempnam(sys_get_temp_dir(), 'ticket_export_');
        if (false === $temporaryFile) {
            throw new BadRequestHttpException('The ticket export file could not be created.');
        }
        (new Xls($spreadsheet))->save($temporaryFile);
        $spreadsheet->disconnectWorksheets();

        $response = new BinaryFileResponse($temporaryFile);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->setContentDisposition('attachment', 'tickets_'.date('Ymd_His').'.xls');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function getRequestData(Request $request): array
    {
        $decoded = json_decode($request->getContent(), true);
        if (!\is_array($decoded)) {
            throw new BadRequestHttpException('The request body must contain valid JSON.');
        }

        return $decoded;
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
