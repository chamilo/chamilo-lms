<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Controller\Api\BaseResourceFileAction;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        EntityManager $entityManager
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
        $resource = new PersonalFile();

        $result = $this->baseResourceFileAction->handleCreateFileRequest(
            $resource,
            $this->personalFileRepository,
            $request,
            $this->entityManager,
            'overwrite'
        );

        $this->entityManager->persist($resource);
        $this->entityManager->flush();

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
