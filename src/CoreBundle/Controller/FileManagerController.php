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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    #[Route('/list', name: 'file_manager_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Implement logic to list files and folders
        // This could be a call to your service or logic to retrieve files/folders
        return $this->json(['files' => []]);
    }

    #[Route('/upload', name: 'file_manager_upload', methods: ['POST'])]
    public function upload(): JsonResponse
    {
        // Implement logic to upload files
        // This part will handle receiving and storing uploaded files
        return $this->json(['message' => 'File(s) uploaded successfully']);
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

    #[Route('/create-folder', name: 'file_manager_create_folder', methods: ['POST'])]
    public function createFolder(): JsonResponse
    {
        // Implement logic to create new folders
        return $this->json(['message' => 'Folder created successfully']);
    }

    #[Route('/rename', name: 'file_manager_rename', methods: ['POST'])]
    public function rename(): JsonResponse
    {
        // Implement logic to rename files/folders
        return $this->json(['message' => 'File/folder renamed successfully']);
    }

    #[Route('/delete', name: 'file_manager_delete', methods: ['DELETE'])]
    public function delete(): JsonResponse
    {
        // Implement logic to delete files/folders
        return $this->json(['message' => 'File/folder deleted successfully']);
    }

    #[Route('/download/{filename}', name: 'file_manager_download', methods: ['GET'])]
    public function download(string $filename): Response
    {
        // Implement logic to download files
        // Replace 'path/to/your/files' with the actual path where the files are stored
        $filePath = 'path/to/your/files/'.$filename;

        return new BinaryFileResponse($filePath);
    }
}
