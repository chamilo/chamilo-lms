<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\ResourceFileHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document as CourseCopyDocument;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Doctrine\ORM\EntityManagerInterface;
use DocumentManager;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

final class LearningPathChamiloBackupExportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly ResourceFileHelper $resourceFileHelper,
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.cache_dir%')]
        private readonly string $cacheDir,
    ) {}

    public function export(CLp $learningPath, Course $course, ?Session $session): string
    {
        $learningPathId = (int) $learningPath->getIid();
        if ($learningPathId <= 0) {
            throw new RuntimeException('The learning path cannot be exported without an identifier.');
        }

        $courseCode = trim((string) $course->getCode());
        if ('' === $courseCode) {
            throw new RuntimeException('The course code is missing.');
        }

        $courseInfo = api_get_course_info($courseCode);
        if (!\is_array($courseInfo) || '' === trim((string) ($courseInfo['code'] ?? ''))) {
            throw new RuntimeException('The course information could not be loaded.');
        }

        $stagingRoot = rtrim($this->cacheDir, '/').'/lp-chamilo-export/'.bin2hex(random_bytes(16));
        $archivePath = null;

        $this->filesystem->mkdir($stagingRoot, 0775);

        try {
            $builder = new CourseBuilder('partial', $courseInfo);
            $builder->course->path = rtrim($stagingRoot, '/').'/';
            $builder->course->backup_path = rtrim($stagingRoot, '/').'/';

            // Initializes the builder's course/session context without exporting a full course.
            $builder->set_tools_to_build([]);
            $builder->build(
                (int) ($session?->getId() ?? 0),
                $courseCode,
                true,
            );

            $dependencies = $this->collectDependencies($learningPath);

            if ([] !== $dependencies['documents']) {
                $documentIds = $this->withDocumentParents(
                    $dependencies['documents'],
                    $courseCode,
                    (int) ($session?->getId() ?? 0),
                );
                $builder->build_documents(
                    (int) ($session?->getId() ?? 0),
                    (int) $course->getId(),
                    true,
                    $documentIds,
                );
                $this->collectEmbeddedDocumentReferences($builder);
            }

            if ([] !== $dependencies['quizzes']) {
                $questionIds = $builder->build_quizzes(
                    $builder->course,
                    $course,
                    $session,
                    $dependencies['quizzes'],
                );
                $builder->build_quiz_questions(
                    $builder->course,
                    $course,
                    $session,
                    $questionIds,
                );
            }

            if ([] !== $dependencies['forumCategories']) {
                $builder->build_forum_category(
                    $builder->course,
                    $course,
                    $session,
                    $dependencies['forumCategories'],
                );
            }

            if ([] !== $dependencies['forums']) {
                $builder->build_forums(
                    $builder->course,
                    $course,
                    $session,
                    $dependencies['forums'],
                );
            }

            if ([] !== $dependencies['threads']) {
                $builder->build_forum_topics(
                    $builder->course,
                    $course,
                    $session,
                    $dependencies['threads'],
                );
                $builder->build_forum_posts(
                    $builder->course,
                    $course,
                    $session,
                    $dependencies['threads'],
                );
            }

            if ([] !== $dependencies['links']) {
                $builder->build_links(
                    $builder->course,
                    $course,
                    $session,
                    $dependencies['links'],
                );
            }

            if ([] !== $dependencies['works']) {
                $builder->build_works(
                    $builder->course,
                    $course,
                    $session,
                    $dependencies['works'],
                );
            }

            $categoryId = (int) ($learningPath->getCategory()?->getIid() ?? 0);
            if ($categoryId > 0) {
                $builder->build_learnpath_category(
                    $builder->course,
                    $course,
                    $session,
                    [$categoryId],
                );
            }

            $builder->build_learnpaths(
                $builder->course,
                $course,
                $session,
                [$learningPathId],
                false,
            );

            // Adds local images/files referenced from exported HTML fields.
            $builder->restoreDocumentsFromList($course, $session);
            $stagedDocumentCount = $this->stageDocumentResources($builder, $stagingRoot);
            if (0 === $stagedDocumentCount && !$this->hasNonDocumentDependencies($dependencies, $learningPath)) {
                throw new LearningPathBackupEmptyException('There is no exportable content for this learning path.');
            }

            $zipFile = CourseArchiver::createBackup($builder->course);
            $archivePath = CourseArchiver::getBackupDir().$zipFile;

            if (!is_file($archivePath) || !is_readable($archivePath)) {
                throw new RuntimeException('The Chamilo backup archive could not be created.');
            }

            $backupRoot = realpath(CourseArchiver::getBackupDir());
            $resolvedArchive = realpath($archivePath);
            if (false === $backupRoot
                || false === $resolvedArchive
                || !str_starts_with($resolvedArchive, rtrim($backupRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
            ) {
                throw new RuntimeException('The generated archive path is invalid.');
            }

            return $resolvedArchive;
        } catch (LearningPathBackupResourceException $exception) {
            if (null !== $archivePath && is_file($archivePath)) {
                @unlink($archivePath);
            }

            throw $exception;
        } catch (Throwable $exception) {
            if (null !== $archivePath && is_file($archivePath)) {
                @unlink($archivePath);
            }

            throw new RuntimeException('Failed to generate the Chamilo learning path backup.', 0, $exception);
        } finally {
            $this->filesystem->remove($stagingRoot);
        }
    }

    /**
     * @return array{
     *     documents: list<int>,
     *     quizzes: list<int>,
     *     threads: list<int>,
     *     forums: list<int>,
     *     forumCategories: list<int>,
     *     links: list<int>,
     *     works: list<int>
     * }
     */
    private function collectDependencies(CLp $learningPath): array
    {
        $documents = [];
        $quizzes = [];
        $threads = [];
        $forums = [];
        $forumCategories = [];
        $links = [];
        $works = [];

        /** @var CLpItem $item */
        foreach ($learningPath->getItems() as $item) {
            $type = strtolower(trim((string) $item->getItemType()));
            $resourceId = $this->positiveNumericId($item->getPath());
            if ($resourceId <= 0) {
                continue;
            }

            switch ($type) {
                case 'document':
                case 'readout_text':
                case 'final_item':
                case 'video':
                    $documents[$resourceId] = $resourceId;
                    break;

                case 'quiz':
                case 'exercise':
                    $quizzes[$resourceId] = $resourceId;
                    break;

                case 'thread':
                    $threads[$resourceId] = $resourceId;
                    $thread = $this->entityManager->getRepository(CForumThread::class)->find($resourceId);
                    if ($thread instanceof CForumThread) {
                        $forum = $thread->getForum();
                        $forumId = (int) ($forum?->getIid() ?? 0);
                        if ($forumId > 0) {
                            $forums[$forumId] = $forumId;
                        }
                        $categoryId = (int) ($forum?->getForumCategory()?->getIid() ?? 0);
                        if ($categoryId > 0) {
                            $forumCategories[$categoryId] = $categoryId;
                        }
                    }
                    break;

                case 'forum':
                    $forums[$resourceId] = $resourceId;
                    $forum = $this->entityManager->getRepository(CForum::class)->find($resourceId);
                    if ($forum instanceof CForum) {
                        $categoryId = (int) ($forum->getForumCategory()?->getIid() ?? 0);
                        if ($categoryId > 0) {
                            $forumCategories[$categoryId] = $categoryId;
                        }
                    }
                    break;

                case 'link':
                case 'weblink':
                case 'url':
                    $links[$resourceId] = $resourceId;
                    break;

                case 'student_publication':
                case 'work':
                    $works[$resourceId] = $resourceId;
                    break;
            }
        }

        return [
            'documents' => array_values($documents),
            'quizzes' => array_values($quizzes),
            'threads' => array_values($threads),
            'forums' => array_values($forums),
            'forumCategories' => array_values($forumCategories),
            'links' => array_values($links),
            'works' => array_values($works),
        ];
    }

    private function positiveNumericId(mixed $value): int
    {
        $value = trim((string) $value);

        return '' !== $value && ctype_digit($value) ? (int) $value : 0;
    }

    /**
     * @param list<int> $documentIds
     *
     * @return list<int>
     */
    private function withDocumentParents(array $documentIds, string $courseCode, int $sessionId): array
    {
        $expanded = [];

        foreach ($documentIds as $documentId) {
            $expanded[$documentId] = $documentId;
            $documentInfo = DocumentManager::get_document_data_by_id(
                $documentId,
                $courseCode,
                true,
                $sessionId,
            );
            if (!\is_array($documentInfo)) {
                continue;
            }

            foreach (($documentInfo['parents'] ?? []) as $parentInfo) {
                $parentId = (int) ($parentInfo['iid'] ?? 0);
                if ($parentId > 0) {
                    $expanded[$parentId] = $parentId;
                }
            }
        }

        return array_values($expanded);
    }

    private function collectEmbeddedDocumentReferences(CourseBuilder $builder): void
    {
        foreach ($this->getDocumentResources($builder) as $resource) {
            $documentId = $this->getLegacyResourceId($resource);
            if ($documentId <= 0) {
                continue;
            }

            $document = $this->entityManager->getRepository(CDocument::class)->find($documentId);
            if (!$document instanceof CDocument || !$this->isHtmlDocument($document)) {
                continue;
            }

            $html = $this->readDocumentTextContent($document);
            if ('' !== trim($html)) {
                $builder->findAndSetDocumentsInText($html);
            }
        }
    }

    private function isHtmlDocument(CDocument $document): bool
    {
        if ('html' === strtolower(trim((string) $document->getFiletype()))) {
            return true;
        }

        $resourceNode = $document->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return false;
        }

        foreach ($this->getResourceFileCandidates($resourceNode) as $resourceFile) {
            $mimeType = strtolower((string) $resourceFile->getMimeType());
            $extension = strtolower(pathinfo((string) $resourceFile->getOriginalName(), PATHINFO_EXTENSION));

            if ('text/html' === $mimeType || \in_array($extension, ['htm', 'html'], true)) {
                return true;
            }
        }

        return false;
    }

    private function stageDocumentResources(CourseBuilder $builder, string $stagingRoot): int
    {
        $stagedCount = 0;
        $keptResources = [];

        foreach ($this->getDocumentResources($builder) as $resource) {
            $documentId = $this->getLegacyResourceId($resource);
            $relativePath = $this->getLegacyDocumentPath($resource);
            if ($documentId <= 0 || '' === $relativePath) {
                throw new RuntimeException('An exported document has invalid metadata.');
            }

            $targetPath = rtrim($stagingRoot, '/').'/'.$relativePath;
            $document = $this->entityManager->getRepository(CDocument::class)->find($documentId);
            if (!$document instanceof CDocument) {
                $this->logSkippedDocument($documentId, '', 'document entity was not found');
                continue;
            }

            $isFolder = 'folder' === strtolower((string) $document->getFiletype());
            $this->normalizeLegacyDocumentResource($resource, $relativePath, $isFolder);

            if ($isFolder) {
                $this->filesystem->mkdir($targetPath, 0775);
                $keptResources[] = $resource;
                continue;
            }

            $resourceNode = $document->getResourceNode();
            if (!$resourceNode instanceof ResourceNode) {
                $this->logSkippedDocument($documentId, (string) $document->getTitle(), 'resource node is missing');
                continue;
            }

            $this->filesystem->mkdir(\dirname($targetPath), 0775);

            $streamData = $this->openReadableResourceStream($resourceNode);
            if (null !== $streamData) {
                [$sourceStream] = $streamData;
                $targetStream = fopen($targetPath, 'wb');
                if (false === $targetStream) {
                    fclose($sourceStream);
                    throw new RuntimeException(sprintf('Document %d could not be staged.', $documentId));
                }

                try {
                    if (false === stream_copy_to_stream($sourceStream, $targetStream)) {
                        throw new RuntimeException(sprintf('Document %d could not be copied.', $documentId));
                    }
                } finally {
                    fclose($sourceStream);
                    fclose($targetStream);
                }

                $keptResources[] = $resource;
                ++$stagedCount;

                continue;
            }

            $fallbackContent = $this->getDocumentFallbackContent($document);
            if ($this->isTextDocument($document) && '' !== trim($fallbackContent)) {
                if (false === file_put_contents($targetPath, $fallbackContent, LOCK_EX)) {
                    throw new RuntimeException(sprintf('Document %d fallback content could not be staged.', $documentId));
                }

                error_log(sprintf(
                    '[LearningPathBackup] Used ResourceNode content fallback for document %d.',
                    $documentId,
                ));

                $keptResources[] = $resource;
                ++$stagedCount;

                continue;
            }

            $this->logSkippedDocument(
                $documentId,
                (string) $document->getTitle(),
                'stored file is missing',
                $this->getExpectedResourceFileName($resourceNode),
            );
        }

        $builder->course->resources[RESOURCE_DOCUMENT] = $keptResources;

        return $stagedCount;
    }

    /**
     * @return array{0: resource, 1: ResourceFile}|null
     */
    private function openReadableResourceStream(ResourceNode $resourceNode): ?array
    {
        foreach ($this->getResourceFileCandidates($resourceNode) as $resourceFile) {
            try {
                $storagePath = $this->resourceNodeRepository->getFilename($resourceFile);
                if (null === $storagePath || '' === trim($storagePath)) {
                    continue;
                }

                $filesystem = $this->resourceNodeRepository->getFileSystem();
                if (!$filesystem->fileExists($storagePath)) {
                    continue;
                }

                $stream = $filesystem->readStream($storagePath);
                if (\is_resource($stream)) {
                    return [$stream, $resourceFile];
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function readDocumentTextContent(CDocument $document): string
    {
        $resourceNode = $document->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return '';
        }

        foreach ($this->getResourceFileCandidates($resourceNode) as $resourceFile) {
            try {
                $storagePath = $this->resourceNodeRepository->getFilename($resourceFile);
                if (null === $storagePath || '' === trim($storagePath)) {
                    continue;
                }

                $filesystem = $this->resourceNodeRepository->getFileSystem();
                if ($filesystem->fileExists($storagePath)) {
                    return $filesystem->read($storagePath);
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $this->getDocumentFallbackContent($document);
    }

    private function getDocumentFallbackContent(CDocument $document): string
    {
        return (string) ($document->getResourceNode()?->getContent() ?? '');
    }

    private function isTextDocument(CDocument $document): bool
    {
        if (\in_array(strtolower(trim((string) $document->getFiletype())), ['html', 'text'], true)) {
            return true;
        }

        $resourceNode = $document->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return false;
        }

        foreach ($this->getResourceFileCandidates($resourceNode) as $resourceFile) {
            $mimeType = strtolower(trim((string) $resourceFile->getMimeType()));
            if (str_starts_with($mimeType, 'text/')) {
                return true;
            }

            $extension = strtolower(pathinfo((string) $resourceFile->getOriginalName(), PATHINFO_EXTENSION));
            if (\in_array($extension, ['css', 'csv', 'htm', 'html', 'js', 'json', 'md', 'txt', 'xml'], true)) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param array{documents: list<int>, quizzes: list<int>, threads: list<int>, forums: list<int>, forumCategories: list<int>, links: list<int>, works: list<int>} $dependencies
     */
    private function hasNonDocumentDependencies(array $dependencies, CLp $learningPath): bool
    {
        foreach (['quizzes', 'threads', 'forums', 'forumCategories', 'links', 'works'] as $key) {
            if ([] !== ($dependencies[$key] ?? [])) {
                return true;
            }
        }

        return null !== $learningPath->getCategory();
    }

    private function logSkippedDocument(
        int $documentId,
        string $title,
        string $reason,
        string $expectedFile = '',
    ): void {
        $message = sprintf(
            '[LearningPathBackup] Skipped document %d "%s" because %s.',
            $documentId,
            $title,
            $reason,
        );

        if ('' !== trim($expectedFile)) {
            $message .= sprintf(' Expected file: %s.', $expectedFile);
        }

        error_log($message);
    }

    /** @return list<ResourceFile> */
    private function getResourceFileCandidates(ResourceNode $resourceNode): array
    {
        $candidates = [];
        $seen = [];

        try {
            $preferred = $this->resourceFileHelper->resolveResourceFileByAccessUrl($resourceNode);
            if ($preferred instanceof ResourceFile) {
                $key = $preferred->getId() ?? spl_object_id($preferred);
                $seen[(string) $key] = true;
                $candidates[] = $preferred;
            }
        } catch (Throwable) {
            // Fall back to all variants attached to the resource node.
        }

        foreach ($resourceNode->getResourceFiles() as $resourceFile) {
            if (!$resourceFile instanceof ResourceFile) {
                continue;
            }

            $key = $resourceFile->getId() ?? spl_object_id($resourceFile);
            if (isset($seen[(string) $key])) {
                continue;
            }

            $seen[(string) $key] = true;
            $candidates[] = $resourceFile;
        }

        return $candidates;
    }

    private function getExpectedResourceFileName(ResourceNode $resourceNode): string
    {
        foreach ($this->getResourceFileCandidates($resourceNode) as $resourceFile) {
            $fileName = trim((string) ($resourceFile->getOriginalName() ?: $resourceFile->getTitle()));
            if ('' !== $fileName) {
                return $fileName;
            }
        }

        return '';
    }

    /** @return list<CourseCopyDocument> */
    private function getDocumentResources(CourseBuilder $builder): array
    {
        $resources = $builder->course->resources[RESOURCE_DOCUMENT] ?? [];
        if (!\is_array($resources)) {
            return [];
        }

        return array_values(array_filter(
            $resources,
            static fn (mixed $resource): bool => $resource instanceof CourseCopyDocument,
        ));
    }

    private function getLegacyResourceId(CourseCopyDocument $resource): int
    {
        return (int) $resource->get_id();
    }

    private function getLegacyDocumentPath(CourseCopyDocument $resource): string
    {
        $path = str_replace('\\', '/', trim((string) ($resource->path ?? '')));
        $path = ltrim($path, '/');

        $path = preg_replace('~^document/(?:document/)+~', 'document/', $path) ?? $path;

        if ('' === $path
            || !str_starts_with($path, 'document/')
            || str_contains($path, "\0")
            || preg_match('~(^|/)\.\.(/|$)~', $path)
        ) {
            return '';
        }

        return preg_replace('~/+~', '/', $path) ?? '';
    }

    private function normalizeLegacyDocumentResource(
        CourseCopyDocument $resource,
        string $path,
        bool $isFolder,
    ): void {
        $resource->path = $path;
        $resource->full_path = $path;

        $legacyFileType = $isFolder ? 'folder' : 'document';
        $resource->file_type = $legacyFileType;
        $resource->filetype = $legacyFileType;
    }
}
