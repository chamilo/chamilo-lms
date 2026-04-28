<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\ResourceFileHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\GroupVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceFileVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\ArrayParameterType;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

class DownloadSelectedDocumentsAction
{
    public const CONTENT_TYPE = 'application/zip';

    public function __construct(
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly CDocumentRepository $documentRepo,
        private readonly ResourceFileHelper $resourceFileHelper,
        private readonly Security $security,
        private readonly CidReqHelper $cidReqHelper,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(Request $request): Response
    {
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        $this->validateApiKeyDocumentDownloadSetting($request);

        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return new Response('Invalid JSON payload.', Response::HTTP_BAD_REQUEST);
        }

        $documentIds = $this->getDocumentIds($data);
        $compressed = $this->resolveCompressedFlag($data);

        if (empty($documentIds)) {
            return new Response('No items selected.', Response::HTTP_BAD_REQUEST);
        }

        $documents = $this->findDocuments($documentIds);

        if (empty($documents)) {
            return new Response('No documents found.', Response::HTTP_NOT_FOUND);
        }

        if (!$compressed) {
            if (1 !== \count($documents)) {
                throw new BadRequestHttpException('Uncompressed download requires exactly one document.');
            }

            return $this->downloadSingleDocument($documents[0]);
        }

        return $this->downloadCompressedDocuments($documents);
    }

    /**
     * @param array<int, int> $documentIds
     *
     * @return array<int, CDocument>
     */
    private function findDocuments(array $documentIds): array
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $this->documentRepo->findBy(['iid' => $documentIds]);
        }

        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();
        $group = $this->cidReqHelper->getGroupEntity();

        if (!$course || !$this->security->isGranted(CourseVoter::VIEW, $course)) {
            throw new NotAllowedException("You're not allowed in this course");
        }

        if ($session && !$this->security->isGranted(SessionVoter::VIEW, $session)) {
            throw new NotAllowedException("You're not allowed in this session");
        }

        if ($group && !$this->security->isGranted(GroupVoter::VIEW, $group)) {
            throw new NotAllowedException("You're not allowed in this group");
        }

        $qb = $this->documentRepo->getResourcesByCourse($course, $session, $group);

        $qb
            ->andWhere($qb->expr()->in('resource.iid', ':documentIds'))
            ->setParameter('documentIds', $documentIds, ArrayParameterType::INTEGER)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<int, CDocument> $documents
     */
    private function downloadCompressedDocuments(array $documents): StreamedResponse
    {
        $zipName = 'selected_documents.zip';

        $response = new StreamedResponse(
            function () use ($documents, $zipName): void {
                $options = new Archive();
                $options->setSendHttpHeaders(false);
                $options->setContentType(self::CONTENT_TYPE);

                $zip = new ZipStream($zipName, $options);

                foreach ($documents as $document) {
                    $node = $document->getResourceNode();

                    if (!$node instanceof ResourceNode) {
                        error_log('ResourceNode not found for document ID: '.$document->getIid());

                        continue;
                    }

                    $this->addNodeToZip($zip, $node);
                }

                if (0 === \count($zip->files)) {
                    $zip->addFile('.empty', '');
                }

                $zip->finish();
            },
            Response::HTTP_CREATED
        );

        $safeZipName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $zipName);

        if (false === $safeZipName || '' === trim($safeZipName)) {
            $safeZipName = 'selected_documents.zip';
        }

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $safeZipName
        );

        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', self::CONTENT_TYPE);

        return $response;
    }

    private function downloadSingleDocument(CDocument $document): StreamedResponse
    {
        if ('file' !== $document->getFiletype()) {
            throw new BadRequestHttpException('Uncompressed download only supports file documents.');
        }

        $node = $document->getResourceNode();

        if (!$node instanceof ResourceNode) {
            throw new NotFoundHttpException('Document resource node not found.');
        }

        $resourceFile = $this->resourceFileHelper->resolveResourceFileByAccessUrl($node);

        if (!$resourceFile) {
            throw new NotFoundHttpException('Document resource file not found.');
        }

        if (!$this->security->isGranted(ResourceFileVoter::DOWNLOAD, $resourceFile)) {
            throw new AccessDeniedHttpException('You are not allowed to download this document.');
        }

        $stream = $this->resourceNodeRepository->getResourceNodeFileStream($node, $resourceFile);

        if (!\is_resource($stream)) {
            throw new NotFoundHttpException('Document file stream not found.');
        }

        $fileName = $resourceFile->getOriginalName() ?: $document->getTitle();
        $fallbackFileName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $fileName);

        if (false === $fallbackFileName || '' === trim($fallbackFileName)) {
            $fallbackFileName = 'document';
        }

        $mimeType = $resourceFile->getMimeType() ?: 'application/octet-stream';

        $response = new StreamedResponse(static function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                if (\is_resource($stream)) {
                    fclose($stream);
                }
            }
        });

        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName,
                $fallbackFileName
            )
        );

        if ($resourceFile->getSize() > 0) {
            $response->headers->set('Content-Length', (string) $resourceFile->getSize());
        }

        return $response;
    }

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

        if (!$resourceFile) {
            return;
        }

        if (!$this->security->isGranted(ResourceFileVoter::DOWNLOAD, $resourceFile)) {
            return;
        }

        $fileName = $currentPath.$resourceFile->getOriginalName();
        $stream = $this->resourceNodeRepository->getResourceNodeFileStream($node, $resourceFile);

        if (!\is_resource($stream)) {
            return;
        }

        try {
            $zip->addFileFromStream($fileName, $stream);
        } finally {
            if (\is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, int>
     */
    private function getDocumentIds(array $data): array
    {
        $ids = $data['ids'] ?? [];

        if (!\is_array($ids)) {
            return [];
        }

        $documentIds = [];

        foreach ($ids as $id) {
            $documentId = (int) $id;

            if (0 < $documentId) {
                $documentIds[$documentId] = $documentId;
            }
        }

        return array_values($documentIds);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveCompressedFlag(array $data): bool
    {
        if (!\array_key_exists('compressed', $data)) {
            return true;
        }

        $value = $data['compressed'];

        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        if (!\is_string($value)) {
            return true;
        }

        return \in_array(strtolower(trim($value)), ['true', '1', 'yes', 'on'], true);
    }

    private function validateApiKeyDocumentDownloadSetting(Request $request): void
    {
        if (true !== $request->attributes->get('_chamilo_webservice_api_key')) {
            return;
        }

        if ($this->isSettingEnabled($this->settingsManager->getSetting('webservice.allow_download_documents_by_api_key', true))) {
            return;
        }

        throw new AccessDeniedHttpException('Document download by API key is disabled.');
    }

    private function isSettingEnabled(mixed $value): bool
    {
        if (true === $value || 1 === $value || '1' === $value) {
            return true;
        }

        if (!\is_string($value)) {
            return false;
        }

        return \in_array(strtolower(trim($value)), ['true', 'yes', 'on'], true);
    }
}
