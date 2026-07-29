<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Document;

use Chamilo\CoreBundle\Cache\DocumentListCacheInvalidator;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Helpers\ResourceHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use RuntimeException;
use Security;
use Throwable;

use const PATHINFO_EXTENSION;

/**
 * Shared document lookup + read/write mechanics for the MCP read/edit
 * document tools. Write mirrors DocumentParagraphMediaEmbedder::writeDocument()
 * (same filesystem-write + resourceNode/resourceFile update + rollback on
 * failure) — kept as its own copy here rather than extracted from the
 * embedder, so the existing illustrate_document_paragraph tool is untouched.
 */
final readonly class CourseDocumentContentService
{
    public function __construct(
        private CDocumentRepository $documentRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private EntityManager $entityManager,
        private DocumentListCacheInvalidator $cacheInvalidator,
        private ResourceHelper $resourceHelper,
    ) {}

    /**
     * Resolves a document scoped to the base course (same "belongs to this
     * course" definition as list_documents: not a folder, not a template,
     * and linked directly to the course with no session/group/user scoping),
     * by documentId or by an exact title match.
     */
    public function resolveDocument(Course $course, ?int $documentId, ?string $title): CDocument
    {
        $documentId = (null !== $documentId && $documentId > 0) ? $documentId : null;
        $title = null !== $title ? trim($title) : null;
        $title = ('' === $title) ? null : $title;

        if (null === $documentId && null === $title) {
            throw new InvalidArgumentException('Provide either documentId or title.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('document')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->andWhere('document.filetype != :folderType')
            ->andWhere('document.template = :isTemplate')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('folderType', 'folder')
            ->setParameter('isTemplate', false)
        ;

        if (null !== $documentId) {
            $queryBuilder
                ->andWhere('document.iid = :documentId')
                ->setParameter('documentId', $documentId, Types::INTEGER)
            ;
        } else {
            $queryBuilder
                ->andWhere('document.title = :title')
                ->setParameter('title', $title, Types::STRING)
            ;
        }

        /** @var CDocument[] $matches */
        $matches = $queryBuilder->getQuery()->getResult();

        if ([] === $matches) {
            throw new InvalidArgumentException(null !== $documentId ? 'The document was not found in this course.' : \sprintf('No document titled "%s" was found in this course.', $title));
        }

        if (\count($matches) > 1) {
            // Only reachable via the title path: iid is unique.
            throw new InvalidArgumentException(\sprintf('More than one document is titled "%s". Provide documentId to disambiguate.', $title));
        }

        return $matches[0];
    }

    /**
     * Whether a non-folder document already exists with this exact title in
     * the given base-course folder (same "belongs to this course" scoping as
     * resolveDocument/list_documents). Used to reject duplicate document names
     * before creation — Chamilo allows same-named files in the legacy UI, but
     * MCP-created documents must be unambiguous for later title-based lookups.
     */
    public function titleExistsInParentFolder(
        Course $course,
        int $parentResourceNodeId,
        string $title,
        ?int $excludeDocumentId = null,
    ): bool
    {
        $title = trim($title);
        if ('' === $title) {
            return false;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(document.iid)')
            ->from(CDocument::class, 'document')
            ->innerJoin('document.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->andWhere('IDENTITY(node.parent) = :parentNodeId')
            ->andWhere('document.title = :title')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('parentNodeId', $parentResourceNodeId, Types::INTEGER)
            ->setParameter('title', $title, Types::STRING)
        ;

        if (null !== $excludeDocumentId && $excludeDocumentId > 0) {
            $queryBuilder
                ->andWhere('document.iid != :excludeDocumentId')
                ->setParameter('excludeDocumentId', $excludeDocumentId, Types::INTEGER)
            ;
        }

        $count = (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    public function createUniqueTitle(
        Course $course,
        int $parentResourceNodeId,
        string $requestedTitle,
        int $maximumLength = 250,
        ?int $excludeDocumentId = null,
    ): string {
        $requestedTitle = trim($requestedTitle);
        if (!$this->titleExistsInParentFolder($course, $parentResourceNodeId, $requestedTitle, $excludeDocumentId)) {
            return $requestedTitle;
        }

        for ($suffix = 2; $suffix <= 999; ++$suffix) {
            $suffixText = ' ('.$suffix.')';
            $baseLength = max(1, $maximumLength - mb_strlen($suffixText));
            $candidate = rtrim(mb_substr($requestedTitle, 0, $baseLength)).$suffixText;

            if (!$this->titleExistsInParentFolder($course, $parentResourceNodeId, $candidate, $excludeDocumentId)) {
                return $candidate;
            }
        }

        throw new RuntimeException('A unique document title could not be generated.');
    }

    public function assertEditableHtmlDocument(CDocument $document): void
    {
        if ('file' !== $document->getFiletype()) {
            throw new InvalidArgumentException('The document must be a file.');
        }

        if ($document->getReadonly()) {
            throw new InvalidArgumentException('The document is read-only.');
        }

        $resourceFile = $document->getResourceNode()?->getFirstResourceFile();
        if (!$resourceFile instanceof ResourceFile) {
            throw new InvalidArgumentException('The document has no stored file.');
        }

        $mimeType = strtolower(trim((string) $resourceFile->getMimeType()));
        $fileName = strtolower(
            (string) ($resourceFile->getOriginalName() ?: $resourceFile->getTitle())
        );
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if ('text/html' !== $mimeType && !\in_array($extension, ['html', 'htm'], true)) {
            throw new InvalidArgumentException('The document must be an editable HTML file.');
        }
    }

    public function readContent(CDocument $document): string
    {
        return $this->documentRepository->getResourceFileContent($document);
    }

    public function sanitizeHtml(string $content): string
    {
        if (!class_exists(Security::class)) {
            throw new RuntimeException('The Chamilo HTML security service is unavailable.');
        }

        if (\defined('COURSEMANAGERLOWSECURITY')) {
            return (string) Security::remove_XSS(
                $content,
                (int) \constant('COURSEMANAGERLOWSECURITY'),
            );
        }

        return (string) Security::remove_XSS($content);
    }

    public function writeContent(CDocument $document, string $newContent): void
    {
        $resourceNode = $document->getResourceNode();
        if (null === $resourceNode) {
            throw new RuntimeException('The document resource node is missing.');
        }

        $resourceFile = $resourceNode->getFirstResourceFile();
        if (!$resourceFile instanceof ResourceFile) {
            throw new RuntimeException('The document resource file is missing.');
        }

        $filename = $this->resourceNodeRepository->getFilename($resourceFile);
        if (!\is_string($filename) || '' === trim($filename)) {
            throw new RuntimeException('The document storage path is missing.');
        }

        $filesystem = $this->resourceNodeRepository->getFileSystem();
        $originalContent = $this->readContent($document);

        try {
            $this->entityManager->wrapInTransaction(
                function () use (
                    $document,
                    $resourceNode,
                    $resourceFile,
                    $filesystem,
                    $filename,
                    $newContent,
                ): void {
                    $filesystem->write($filename, $newContent);

                    $resourceNode->setContent($newContent);
                    $resourceNode->setUpdatedAt(new DateTime());
                    $resourceFile
                        ->setSize(\strlen($newContent))
                        ->setMimeType('text/html')
                    ;

                    $this->entityManager->persist($document);
                    $this->entityManager->persist($resourceNode);
                    $this->entityManager->persist($resourceFile);
                }
            );
        } catch (Throwable $throwable) {
            try {
                $filesystem->write($filename, $originalContent);
            } catch (Throwable) {
                // Keep the original exception. The log records the storage failure.
            }

            throw new RuntimeException('The document could not be stored.', 0, $throwable);
        }

        $this->cacheInvalidator->invalidate();

        try {
            $this->resourceHelper->createAndSaveResourceEvent($resourceNode, 'edition');
        } catch (Throwable) {
            // Tracking must not turn a completed document update into a tool failure.
        }
    }
}
