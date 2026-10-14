<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\SupportBotTree;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\SupportBot\SupportBotAdminService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

#[AsController]
#[Route('/api/support-bot/admin/trees')]
#[IsGranted('ROLE_ADMIN')]
final readonly class SupportBotAdminController
{
    public function __construct(
        private SupportBotAdminService $adminService,
        private Security $security,
    ) {}

    #[Route('', name: 'support_bot_admin_tree_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $tree = $this->adminService->saveTree(null, $this->getAuthenticatedUser(), $this->getRequestData($request));

        return $this->treeResponse($tree, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'support_bot_admin_tree_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $tree = $this->adminService->saveTree($id, $this->getAuthenticatedUser(), $this->getRequestData($request));

        return $this->treeResponse($tree);
    }

    #[Route('/{id}/publish', name: 'support_bot_admin_tree_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publish(int $id): JsonResponse
    {
        return $this->treeResponse($this->adminService->publishTree($id));
    }

    #[Route('/{id}/archive', name: 'support_bot_admin_tree_archive', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function archive(int $id): JsonResponse
    {
        return $this->treeResponse($this->adminService->archiveTree($id));
    }

    #[Route('/{id}/duplicate', name: 'support_bot_admin_tree_duplicate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function duplicate(int $id): JsonResponse
    {
        $tree = $this->adminService->duplicateTree($id, $this->getAuthenticatedUser());

        return $this->treeResponse($tree, Response::HTTP_CREATED);
    }

    #[Route('/import', name: 'support_bot_admin_tree_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('A valid support bot import file is required.');
        }
        $size = $file->getSize();
        if (false !== $size && $size > 1_048_576) {
            throw new BadRequestHttpException('The support bot import file cannot be larger than 1 MB.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!\in_array($extension, ['json', 'csv'], true)) {
            throw new BadRequestHttpException('Only JSON and CSV support bot imports are accepted.');
        }

        $contents = file_get_contents($file->getPathname());
        if (false === $contents) {
            throw new BadRequestHttpException('The support bot import file could not be read.');
        }

        $tree = $this->adminService->importTree($this->getAuthenticatedUser(), $contents, $extension);

        return $this->treeResponse($tree, Response::HTTP_CREATED);
    }

    #[Route('/{id}/export', name: 'support_bot_admin_tree_export', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function export(int $id): JsonResponse
    {
        return new JsonResponse(
            $this->adminService->exportTree($id),
            Response::HTTP_OK,
            [
                'Content-Disposition' => 'attachment; filename="support-bot-tree-'.$id.'.json"',
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    #[Route('/{id}', name: 'support_bot_admin_tree_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->adminService->deleteTree($id);

        return new JsonResponse(['message' => 'Support bot tree deleted.']);
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
            throw new BadRequestHttpException('The authenticated administrator user is required.');
        }

        return $user;
    }

    private function treeResponse(SupportBotTree $tree, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'id' => (int) $tree->getId(),
            'title' => $tree->getTitle(),
            'status' => $tree->getStatus(),
            'version' => $tree->getVersion(),
        ], $status);
    }
}
