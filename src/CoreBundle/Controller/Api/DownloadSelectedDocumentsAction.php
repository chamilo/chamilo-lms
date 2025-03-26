<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use ZipArchive;

class DownloadSelectedDocumentsAction
{
    private KernelInterface $kernel;
    private ResourceNodeRepository $resourceNodeRepository;

    public function __construct(KernelInterface $kernel, ResourceNodeRepository $resourceNodeRepository)
    {
        $this->kernel = $kernel;
        $this->resourceNodeRepository = $resourceNodeRepository;
    }

    public function __invoke(Request $request, EntityManagerInterface $em): Response
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        $data = json_decode($request->getContent(), true);
        $documentIds = $data['ids'] ?? [];

        if (empty($documentIds)) {
            return new Response('No items selected.', Response::HTTP_BAD_REQUEST);
        }

        $documents = $em->getRepository(CDocument::class)->findBy(['iid' => $documentIds]);

        if (empty($documents)) {
            return new Response('No documents found.', Response::HTTP_NOT_FOUND);
        }

        $zipFilePath = $this->createZipFile($documents);

        if (!$zipFilePath || !file_exists($zipFilePath)) {
            return new Response('ZIP file not found or could not be created.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $fileSize = filesize($zipFilePath);
        if (false === $fileSize || 0 === $fileSize) {
            error_log('ZIP file is empty or unreadable.');

            throw new Exception('ZIP file is empty or unreadable.');
        }

        $response = new StreamedResponse(function () use ($zipFilePath): void {
            $handle = fopen($zipFilePath, 'rb');
            if ($handle) {
                while (!feof($handle)) {
                    echo fread($handle, 8192);
                    ob_flush();
                    flush();
                }
                fclose($handle);
            }
        });

        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'inline; filename="selected_documents.zip"');
        $response->headers->set('Content-Length', (string) $fileSize);

        return $response;
    }

    /**
     * Creates a ZIP file containing the specified documents.
     *
     * @return string the path to the created ZIP file
     *
     * @throws Exception if the ZIP file cannot be created or closed
     */
    private function createZipFile(array $documents): string
    {
        $cacheDir = $this->kernel->getCacheDir();
        $zipFilePath = $cacheDir.'/selected_documents_'.uniqid().'.zip';

        $zip = new ZipArchive();
        $result = $zip->open($zipFilePath, ZipArchive::CREATE);

        if (true !== $result) {
            throw new Exception('Unable to create ZIP file');
        }

        $projectDir = $this->kernel->getProjectDir();
        $baseUploadDir = $projectDir.'/var/upload/resource';

        foreach ($documents as $document) {
            $resourceNode = $document->getResourceNode();
            if (!$resourceNode) {
                error_log('ResourceNode not found for document ID: '.$document->getId());

                continue;
            }

            $this->addNodeToZip($zip, $resourceNode, $baseUploadDir);
        }

        if (!$zip->close()) {
            error_log('Failed to close ZIP file.');

            throw new Exception('Failed to close ZIP archive');
        }

        return $zipFilePath;
    }

    /**
     * Adds a resource node and its files or children to the ZIP archive.
     */
    private function addNodeToZip(ZipArchive $zip, ResourceNode $node, string $baseUploadDir, string $currentPath = ''): void
    {
        if ($node->getChildren()->count() > 0) {
            $relativePath = $currentPath.$node->getTitle().'/';
            $zip->addEmptyDir($relativePath);

            foreach ($node->getChildren() as $childNode) {
                $this->addNodeToZip($zip, $childNode, $baseUploadDir, $relativePath);
            }
        } elseif ($node->hasResourceFile()) {
            foreach ($node->getResourceFiles() as $resourceFile) {
                $filePath = $baseUploadDir.$this->resourceNodeRepository->getFilename($resourceFile);
                $fileName = $currentPath.$resourceFile->getOriginalName();

                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $fileName);
                } else {
                    error_log('File not found: '.$filePath);
                }
            }
        } else {
            error_log('Node has no children or files: '.$node->getTitle());
        }
    }
}
