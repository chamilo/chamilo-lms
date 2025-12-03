<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Helpers\ResourceFileHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class DownloadSelectedDocumentsAction
{
    use ControllerTrait;

    public const CONTENT_TYPE = 'application/zip';

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly CDocumentRepository $documentRepo,
        private readonly ResourceFileHelper $resourceFileHelper,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(Request $request): Response
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        $data = json_decode($request->getContent(), true);
        $documentIds = $data['ids'] ?? [];

        if (empty($documentIds)) {
            return new Response('No items selected.', Response::HTTP_BAD_REQUEST);
        }

        $documents = $this->documentRepo->findBy(['iid' => $documentIds]);

        if (empty($documents)) {
            return new Response('No documents found.', Response::HTTP_NOT_FOUND);
        }

        $zipName = 'selected_documents.zip';

        $response = new StreamedResponse(
            function () use ($documents, $zipName): void {
                // Creates a ZIP file containing the specified documents.
                $options = new Archive();
                $options->setSendHttpHeaders(false);
                $options->setContentType(self::CONTENT_TYPE);

                $zip = new ZipStream($zipName, $options);

                foreach ($documents as $document) {
                    $node = $document->getResourceNode();

                    if (!$node) {
                        error_log('ResourceNode not found for document ID: '.$document->getIid());

                        continue;
                    }

                    $this->addNodeToZip($zip, $node);
                }

                if (0 === count($zip->files)) {
                    $zip->addFile('.empty', '');
                }

                $zip->finish();
            },
            Response::HTTP_CREATED
        );

        // Convert the file name to ASCII using iconv
        $zipName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $zipName);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $zipName
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', self::CONTENT_TYPE);

        return $response;
    }

    /**
     * Adds a resource node and its files or children to the ZIP archive.
     */
    private function addNodeToZip(ZipStream $zip, ResourceNode $node, string $currentPath = ''): void
    {
        if ($node->getChildren()->count() > 0) {
            $relativePath = $currentPath.$node->getTitle().'/';

            $zip->addFile($relativePath, '');

            foreach ($node->getChildren() as $childNode) {
                $this->addNodeToZip($zip, $childNode, $relativePath);
            }

            return;
        }

        $resourceFile = $this->resourceFileHelper->resolveResourceFileByAccessUrl($node);

        if ($resourceFile) {
            $fileName = $currentPath.$resourceFile->getOriginalName();
            $stream = $this->resourceNodeRepository->getResourceNodeFileStream($node, $resourceFile);

            $zip->addFileFromStream($fileName, $stream);
        }
    }
}
