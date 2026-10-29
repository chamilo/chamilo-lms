<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Controller\Api\BaseResourceFileAction;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Service\Storage\StorageQuotaService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/file-manager')]
class FileManagerController extends AbstractController
{
    private BaseResourceFileAction $baseResourceFileAction;
    private PersonalFileRepository $personalFileRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        BaseResourceFileAction $baseResourceFileAction,
        PersonalFileRepository $personalFileRepository,
        EntityManager $entityManager,
        private readonly CidReqHelper $cidReqHelper,
        private readonly StorageQuotaService $storageQuotaService,
        private readonly UserHelper $userHelper,
    ) {
        $this->baseResourceFileAction = $baseResourceFileAction;
        $this->personalFileRepository = $personalFileRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     */
    #[Route('/upload-image', name: 'file_manager_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse
    {
        $user = $this->userHelper->getCurrent();
        if (null === $user) {
            throw $this->createAccessDeniedException('Authentication is required.');
        }

        $payload = json_decode((string) $request->getContent(), true);
        $parentId = $this->storageQuotaService->normalizeParentNodeId(
            $request->request->get('parentResourceNodeId')
                ?? (\is_array($payload) ? ($payload['parentResourceNodeId'] ?? 0) : 0)
        );
        $this->storageQuotaService->assertPersonalParent($user, $parentId);

        $file = $request->files->get('uploadFile');
        $incomingBytes = $file instanceof UploadedFile
            ? (int) ($file->getSize() ?: 0)
            : ($request->request->has('contentFile')
                ? \strlen((string) $request->request->get('contentFile'))
                : (\is_array($payload) && isset($payload['contentFile']) ? \strlen((string) $payload['contentFile']) : 0));

        [$resource, $result] = $this->entityManager->wrapInTransaction(function () use ($user, $incomingBytes, $request): array {
            $this->storageQuotaService->lockUser($user);
            $this->storageQuotaService->assertPersonalQuota($user, $incomingBytes);

            $resource = new PersonalFile();
            $result = $this->baseResourceFileAction->handleCreateFileRequest(
                $resource,
                $this->personalFileRepository,
                $request,
                $this->entityManager,
                $this->cidReqHelper,
                'overwrite',
                // Never publish a personal file into the course context.
                resourceLinkListOverride: []
            );
            $resource->setCreator($user);
            $this->entityManager->persist($resource);

            return [$resource, $result];
        });

        if (!$result) {
            return $this->json(['error' => 'File upload failed'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'message' => 'File uploaded successfully',
            'data' => $result,
            'location' => $this->personalFileRepository->getResourceFileUrl($resource),
        ]);
    }
}
