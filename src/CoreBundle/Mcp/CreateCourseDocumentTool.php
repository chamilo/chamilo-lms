<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Controller\Api\CreateDocumentFileAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;
use const JSON_THROW_ON_ERROR;
use const PREG_SPLIT_NO_EMPTY;

final readonly class CreateCourseDocumentTool
{
    private const int MAX_CONTENT_LENGTH = 2_000_000;
    private const int MAX_REQUESTED_WORDS = 5_000;
    private const int MIN_REQUESTED_WORDS = 50;

    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private CourseRelUserRepository $courseRelUserRepository,
        private CDocumentRepository $documentRepository,
        private CreateDocumentFileAction $createDocumentFileAction,
        private EntityManagerInterface $entityManager,
        private KernelInterface $kernel,
        private TranslatorInterface $translator,
        private CourseRepository $courseRepository,
        private CourseHelper $courseHelper,
        private AiDisclosureHelper $aiDisclosureHelper,
        private LanguageRepository $languageRepository,
        private CourseDocumentContentService $documentContentService,
    ) {}

    /**
     * @return array{
     *     created: true,
     *     document: array{
     *         document_id: int,
     *         resource_node_id: int,
     *         parent_resource_node_id: int,
     *         title: string,
     *         requested_title: string,
     *         title_adjusted: bool,
     *         file_name: string|null,
     *         language: string,
     *         topic: string,
     *         requested_word_count: int,
     *         actual_word_count: int,
     *         word_count_within_20_percent: bool,
     *         published: bool,
     *         ai_assisted: true,
     *         content_type: 'text/html',
     *         content_url: string
     *     }
     * }
     */
    #[McpTool(
        name: 'create_course_document',
        description: 'Create an AI-assisted HTML document in the root Documents folder of a course managed by the authenticated teacher. The MCP client must supply the generated HTML content. If language is omitted, the course\'s own language is used; otherwise provide either a language name (e.g. "Spanish") or an existing Chamilo language code (e.g. "es").',
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
        try {
            return $this->doCreateCourseDocument(
                $courseId,
                $title,
                $topic,
                $requestedWordCount,
                $content,
                $language,
                $publish,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course document could not be created because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{
     *     created: true,
     *     document: array{
     *         document_id: int,
     *         resource_node_id: int,
     *         parent_resource_node_id: int,
     *         title: string,
     *         requested_title: string,
     *         title_adjusted: bool,
     *         file_name: string|null,
     *         language: string,
     *         topic: string,
     *         requested_word_count: int,
     *         actual_word_count: int,
     *         word_count_within_20_percent: bool,
     *         published: bool,
     *         ai_assisted: true,
     *         content_type: 'text/html',
     *         content_url: string
     *     }
     * }
     */
    private function doCreateCourseDocument(
        int $courseId,
        string $title,
        string $topic,
        int $requestedWordCount,
        string $content,
        ?string $language,
        bool $publish,
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
            throw new AccessDeniedException('The course was not found or is not managed by the authenticated teacher.');
        }

        $title = trim(strip_tags($title));
        if ('' === $title) {
            throw new InvalidArgumentException('The document title is required.');
        }

        if (mb_strlen($title) > 250) {
            throw new InvalidArgumentException('The document title cannot be longer than 250 characters.');
        }

        $courseResourceNode = $course->getResourceNode();
        if (null === $courseResourceNode || null === $courseResourceNode->getId()) {
            throw new RuntimeException('The course resource node could not be resolved.');
        }

        $requestedTitle = $title;
        $title = $this->documentContentService->createUniqueTitle(
            $course,
            (int) $courseResourceNode->getId(),
            $title,
        );

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
            throw new InvalidArgumentException(\sprintf('The requested word count must be between %d and %d.', self::MIN_REQUESTED_WORDS, self::MAX_REQUESTED_WORDS));
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

        $languageIsoCode = $this->resolveLanguageIsoCode($course, $language);

        $visibility = $publish
            ? ResourceLink::VISIBILITY_PUBLISHED
            : ResourceLink::VISIBILITY_DRAFT;

        /** @var CDocument $document */
        $document = $this->entityManager->wrapInTransaction(
            function () use ($courseId, $title, $topic, $content, $languageIsoCode, $visibility, $courseResourceNode): CDocument {
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
                        'language' => $languageIsoCode,
                        'parentResourceNodeId' => (int) $courseResourceNode->getId(),
                        'resourceLinkList' => json_encode(
                            [['visibility' => $visibility]],
                            JSON_THROW_ON_ERROR,
                        ),
                        'ai_assisted' => '1',
                    ],
                    [],
                    [],
                    [],
                    '',
                );

                $document = ($this->createDocumentFileAction)(
                    $request,
                    $this->documentRepository,
                    $this->entityManager,
                    $this->kernel,
                    $this->translator,
                    $this->courseRepository,
                    $this->courseHelper,
                    $this->aiDisclosureHelper,
                );

                $resourceFile = $document->getResourceNode()?->getFirstResourceFile();

                if (!$resourceFile instanceof ResourceFile) {
                    throw new RuntimeException('Chamilo created the HTML document without a resource file.');
                }

                // Vich/Finfo can detect short HTML fragments as text/plain.
                // The tool created and sanitized an HTML file explicitly, so
                // normalize the persisted metadata after Vich processed it.
                $resourceFile->setMimeType('text/html');
                $this->entityManager->persist($resourceFile);

                return $document;
            }
        );

        $documentId = (int) ($document->getIid() ?? 0);
        $resourceNode = $document->getResourceNode();
        $resourceNodeId = (int) ($resourceNode?->getId() ?? 0);

        if ($documentId <= 0 || $resourceNodeId <= 0) {
            throw new RuntimeException('Chamilo created an incomplete document resource.');
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
                'parent_resource_node_id' => (int) $course->getResourceNode()?->getId(),
                'title' => $document->getTitle(),
                'requested_title' => $requestedTitle,
                'title_adjusted' => $requestedTitle !== $document->getTitle(),
                'file_name' => $fileName,
                'language' => $languageIsoCode,
                'topic' => $topic,
                'requested_word_count' => $requestedWordCount,
                'actual_word_count' => $actualWordCount,
                'word_count_within_20_percent' => $actualWordCount >= $minimumExpected
                    && $actualWordCount <= $maximumExpected,
                'published' => $publish,
                'ai_assisted' => true,
                'content_type' => 'text/html',
                'content_url' => $this->documentRepository->getResourceFileUrl(
                    $document,
                    ['cid' => $courseId],
                ),
            ],
        ];
    }

    /**
     * Resolves the document language to an available Chamilo isocode.
     *
     * When no language is requested, the course's own language is used —
     * itself resolved through the same lookup, so a course whose stored
     * language is no longer an available Chamilo language fails loudly
     * instead of silently creating a document with no language set. When a
     * language is requested, it is treated primarily as a human-readable
     * title (e.g. "Spanish") since that is what an AI client naturally
     * produces; an existing isocode is also accepted directly.
     */
    private function resolveLanguageIsoCode(Course $course, ?string $language): string
    {
        $language = null !== $language ? trim($language) : '';

        if ('' === $language) {
            $courseLanguageCode = trim((string) $course->getCourseLanguage());
            $resolved = '' !== $courseLanguageCode
                ? $this->languageRepository->findOneAvailableByTitleOrCode($courseLanguageCode)
                : null;

            if (!$resolved instanceof Language) {
                throw new InvalidArgumentException('The course language could not be resolved to a valid, available Chamilo language.');
            }

            return $resolved->getIsocode();
        }

        $resolved = $this->languageRepository->findOneAvailableByTitleOrCode($language);
        if (!$resolved instanceof Language) {
            throw new InvalidArgumentException(\sprintf('Unknown document language "%s". Provide a language name (e.g. "Spanish") or an existing Chamilo language code (e.g. "es").', $language));
        }

        return $resolved->getIsocode();
    }

    private function sanitizeHtml(string $content): string
    {
        if (!class_exists(\Security::class)) {
            throw new RuntimeException('The Chamilo HTML security service is unavailable.');
        }

        if (\defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS(
                $content,
                (int) \constant('COURSEMANAGERLOWSECURITY'),
            );
        }

        return (string) \Security::remove_XSS($content);
    }

    private function countWords(string $html): int
    {
        $plainText = html_entity_decode(
            strip_tags($html),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
        $plainText = trim((string) preg_replace('/\s+/u', ' ', $plainText));

        if ('' === $plainText) {
            return 0;
        }

        $words = preg_split('/\s+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY);

        return \is_array($words) ? \count($words) : 0;
    }
}
