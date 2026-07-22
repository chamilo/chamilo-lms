<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Controller\Api\CreateDocumentFileAction;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CreateCourseDocumentTool
{
    private const MAX_CONTENT_LENGTH = 2_000_000;
    private const MAX_REQUESTED_WORDS = 5_000;
    private const MIN_REQUESTED_WORDS = 50;

    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private CourseRelUserRepository $courseRelUserRepository,
        private CDocumentRepository $documentRepository,
        private CreateDocumentFileAction $createDocumentFileAction,
        private EntityManager $entityManager,
        private KernelInterface $kernel,
        private TranslatorInterface $translator,
        private CourseRepository $courseRepository,
        private CourseHelper $courseHelper,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @return array{
     *     created: true,
     *     document: array{
     *         document_id: int,
     *         resource_node_id: int,
     *         documents_root_node_id: int,
     *         title: string,
     *         file_name: string|null,
     *         topic: string,
     *         requested_word_count: int,
     *         actual_word_count: int,
     *         word_count_within_20_percent: bool,
     *         published: bool,
     *         ai_assisted: true,
     *         content_url: string
     *     }
     * }
     */
    #[McpTool(
        name: 'create_course_document',
        description: 'Create an AI-assisted HTML document in the root Documents folder of a course managed by the authenticated teacher. The MCP client must supply the generated HTML content.',
    )]
    public function createCourseDocument(
        int $courseId,
        string $title,
        string $topic,
        int $requestedWordCount,
        string $content,
        ?string $language = null,
        bool $publish = true,
    ): array {
        if ($courseId <= 0) {
            throw new InvalidArgumentException('The course ID must be a positive integer.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('An authenticated Chamilo user is required.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new RuntimeException('The current Chamilo access URL could not be resolved.');
        }

        $course = $this->courseRelUserRepository->findTeacherCourseForUserAndAccessUrl(
            $user,
            $accessUrl,
            $courseId,
        );

        if (null === $course) {
            throw new AccessDeniedException(
                'The course was not found or is not managed by the authenticated teacher.'
            );
        }

        $title = trim(strip_tags($title));
        if ('' === $title) {
            throw new InvalidArgumentException('The document title is required.');
        }

        if (mb_strlen($title) > 250) {
            throw new InvalidArgumentException('The document title cannot be longer than 250 characters.');
        }

        $topic = trim(strip_tags($topic));
        if ('' === $topic) {
            throw new InvalidArgumentException('The document topic is required.');
        }

        if (mb_strlen($topic) > 500) {
            throw new InvalidArgumentException('The document topic cannot be longer than 500 characters.');
        }

        if (
            $requestedWordCount < self::MIN_REQUESTED_WORDS
            || $requestedWordCount > self::MAX_REQUESTED_WORDS
        ) {
            throw new InvalidArgumentException(
                \sprintf(
                    'The requested word count must be between %d and %d.',
                    self::MIN_REQUESTED_WORDS,
                    self::MAX_REQUESTED_WORDS
                )
            );
        }

        $content = trim($content);
        if ('' === $content) {
            throw new InvalidArgumentException('The document HTML content is required.');
        }

        if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
            throw new InvalidArgumentException('The document HTML content is too large.');
        }

        $content = $this->sanitizeHtml($content);
        if ('' === trim(strip_tags($content))) {
            throw new InvalidArgumentException('The document content is empty after sanitization.');
        }

        $language = null !== $language ? trim($language) : null;
        if ('' === $language) {
            $language = null;
        }

        if (null !== $language && !preg_match('/^[a-zA-Z0-9_-]{1,8}$/', $language)) {
            throw new InvalidArgumentException('The document language code is invalid.');
        }

        $visibility = $publish
            ? ResourceLink::VISIBILITY_PUBLISHED
            : ResourceLink::VISIBILITY_DRAFT;

        /** @var CDocument $document */
        $document = $this->entityManager->wrapInTransaction(
            function () use ($course, $courseId, $title, $topic, $content, $language, $visibility): CDocument {
                $documentsRoot = $this->documentRepository->ensureCourseDocumentsRootNode($course);

                $request = Request::create(
                    '/api/documents?cid='.$courseId,
                    'POST',
                    [
                        'title' => $title,
                        'filetype' => 'file',
                        'comment' => $topic,
                        'contentFile' => $content,
                        'contentFileExtension' => 'html',
                        'contentFileMimeType' => 'text/html',
                        'language' => $language ?? '',
                        'parentResourceNodeId' => (int) $documentsRoot->getId(),
                        'resourceLinkList' => json_encode(
                            [['visibility' => $visibility]],
                            JSON_THROW_ON_ERROR
                        ),
                        'ai_assisted' => '1',
                    ],
                    [],
                    [],
                    [],
                    ''
                );

                return ($this->createDocumentFileAction)(
                    $request,
                    $this->documentRepository,
                    $this->entityManager,
                    $this->kernel,
                    $this->translator,
                    $this->courseRepository,
                    $this->courseHelper,
                    $this->aiDisclosureHelper,
                );
            }
        );

        $documentId = (int) ($document->getIid() ?? 0);
        $resourceNode = $document->getResourceNode();
        $resourceNodeId = (int) ($resourceNode?->getId() ?? 0);

        if ($documentId <= 0 || $resourceNodeId <= 0) {
            throw new RuntimeException('Chamilo created an incomplete document resource.');
        }

        $documentsRoot = $this->documentRepository->getCourseDocumentsRootNode($course);
        if (null === $documentsRoot || null === $documentsRoot->getId()) {
            throw new RuntimeException('The course Documents root could not be resolved.');
        }

        $resourceFile = $resourceNode?->getFirstResourceFile();
        $fileName = $resourceFile instanceof ResourceFile
            ? ($resourceFile->getOriginalName() ?: $resourceFile->getTitle())
            : null;

        $actualWordCount = $this->countWords($content);
        $minimumExpected = (int) floor($requestedWordCount * 0.8);
        $maximumExpected = (int) ceil($requestedWordCount * 1.2);

        return [
            'created' => true,
            'document' => [
                'document_id' => $documentId,
                'resource_node_id' => $resourceNodeId,
                'documents_root_node_id' => (int) $documentsRoot->getId(),
                'title' => $document->getTitle(),
                'file_name' => $fileName,
                'topic' => $topic,
                'requested_word_count' => $requestedWordCount,
                'actual_word_count' => $actualWordCount,
                'word_count_within_20_percent' => $actualWordCount >= $minimumExpected
                    && $actualWordCount <= $maximumExpected,
                'published' => $publish,
                'ai_assisted' => true,
                'content_url' => $this->documentRepository->getResourceFileUrl(
                    $document,
                    ['cid' => $courseId]
                ),
            ],
        ];
    }

    private function sanitizeHtml(string $content): string
    {
        if (!class_exists(\Security::class)) {
            throw new RuntimeException('The Chamilo HTML security service is unavailable.');
        }

        if (\defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS(
                $content,
                (int) \constant('COURSEMANAGERLOWSECURITY')
            );
        }

        return (string) \Security::remove_XSS($content);
    }

    private function countWords(string $html): int
    {
        $plainText = html_entity_decode(
            strip_tags($html),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $plainText = trim((string) preg_replace('/\s+/u', ' ', $plainText));

        if ('' === $plainText) {
            return 0;
        }

        $words = preg_split('/\s+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY);

        return \is_array($words) ? \count($words) : 0;
    }
}
