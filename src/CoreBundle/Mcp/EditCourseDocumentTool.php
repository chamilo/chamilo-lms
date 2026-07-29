<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class EditCourseDocumentTool
{
    private const int MAX_CONTENT_LENGTH = 2_000_000;

    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseDocumentContentService $documentContentService,
        private CDocumentRepository $documentRepository,
        private LanguageRepository $languageRepository,
        private EntityManagerInterface $entityManager,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @return array{updated: true, changed_fields: list<string>, document: array<string, mixed>}
     */
    #[McpTool(
        name: 'edit_course_document',
        description: 'Edit an existing HTML document in a base course managed by the authenticated teacher. Locate it by documentId or exact title. You may replace its complete HTML content, rename it with newTitle and/or change its Chamilo language. Call read_course_document first before changing content. At least one of content, newTitle or language is required.',
    )]
    public function editCourseDocument(
        int $courseId,
        ?string $content = null,
        ?int $documentId = null,
        ?string $title = null,
        ?string $newTitle = null,
        ?string $language = null,
    ): array {
        try {
            return $this->doEditCourseDocument(
                $courseId,
                $content,
                $documentId,
                $title,
                $newTitle,
                $language,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The document could not be edited because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{updated: true, changed_fields: list<string>, document: array<string, mixed>}
     */
    private function doEditCourseDocument(
        int $courseId,
        ?string $content,
        ?int $documentId,
        ?string $title,
        ?string $newTitle,
        ?string $language,
    ): array {
        $context = $this->courseContext->resolve($courseId);
        $course = $context['course'];
        $user = $context['user'];

        $document = $this->documentContentService->resolveDocument($course, $documentId, $title);
        $this->documentContentService->assertEditableHtmlDocument($document);

        $resourceNode = $document->getResourceNode();
        $resourceFile = $resourceNode?->getFirstResourceFile();
        if (null === $resourceNode || null === $resourceFile) {
            throw new RuntimeException('The document resource metadata could not be resolved.');
        }

        $changedFields = [];
        $requestedNewTitle = null !== $newTitle ? trim(strip_tags($newTitle)) : null;
        if (null !== $requestedNewTitle) {
            if ('' === $requestedNewTitle) {
                throw new InvalidArgumentException('The new document title cannot be empty.');
            }
            if (mb_strlen($requestedNewTitle) > 250) {
                throw new InvalidArgumentException('The new document title cannot be longer than 250 characters.');
            }

            if ($requestedNewTitle !== $document->getTitle()) {
                $parentNodeId = (int) ($resourceNode->getParent()?->getId() ?? 0);
                if ($parentNodeId <= 0) {
                    throw new RuntimeException('The document parent folder could not be resolved.');
                }

                $resolvedNewTitle = $this->documentContentService->createUniqueTitle(
                    $course,
                    $parentNodeId,
                    $requestedNewTitle,
                    250,
                    (int) $document->getIid(),
                );
                $document->setTitle($resolvedNewTitle);
                $resourceNode->setTitle($resolvedNewTitle);
                $changedFields[] = 'title';
            }
        }

        if (null !== $language) {
            $language = trim($language);
            if ('' === $language) {
                throw new InvalidArgumentException('The document language cannot be empty.');
            }

            $resolvedLanguage = $this->languageRepository->findOneAvailableByTitleOrCode($language);
            if (!$resolvedLanguage instanceof Language) {
                throw new InvalidArgumentException(\sprintf('Unknown document language "%s". Provide a language name or an existing Chamilo language code.', $language));
            }

            $currentLanguage = $resourceFile->getLanguage()?->getIsocode();
            if ($resolvedLanguage->getIsocode() !== $currentLanguage) {
                $resourceNode->setLanguage($resolvedLanguage);
                $resourceFile->setLanguage($resolvedLanguage);
                $changedFields[] = 'language';
            }
        }

        if (null !== $content) {
            $content = trim($content);
            if ('' === $content) {
                throw new InvalidArgumentException('The new document content cannot be empty.');
            }
            if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
                throw new InvalidArgumentException('The new document content is too large.');
            }

            $content = $this->documentContentService->sanitizeHtml($content);
            if ('' === trim(strip_tags($content))) {
                throw new InvalidArgumentException('The new document content is empty after sanitization.');
            }

            if ($content !== $this->documentContentService->readContent($document)) {
                $this->documentContentService->writeContent($document, $content);
                $changedFields[] = 'content';
            }
        }

        if ([] === $changedFields) {
            throw new InvalidArgumentException('No document change was provided. Supply content, newTitle and/or language.');
        }

        if (\in_array('title', $changedFields, true) || \in_array('language', $changedFields, true)) {
            $resourceNode->setUpdatedAt(new DateTime());
            $this->entityManager->persist($document);
            $this->entityManager->persist($resourceNode);
            $this->entityManager->persist($resourceFile);
            $this->entityManager->flush();
        }

        $resolvedDocumentId = (int) ($document->getIid() ?? 0);
        if (!$user instanceof User || null === $user->getId()) {
            throw new RuntimeException('The authenticated user could not be resolved.');
        }

        $changedFields = array_values(array_unique($changedFields));
        $this->aiDisclosureHelper->markAiAssistedExtraField('document', $resolvedDocumentId, true);
        $this->aiDisclosureHelper->logAudit(
            targetKey: 'course:'.$courseId.':document:'.$resolvedDocumentId.':edit',
            userId: (int) $user->getId(),
            meta: [
                'feature' => 'edit_course_document',
                'changed_fields' => $changedFields,
            ],
            courseId: $courseId,
        );

        return [
            'updated' => true,
            'changed_fields' => $changedFields,
            'document' => [
                'document_id' => $resolvedDocumentId,
                'title' => $document->getTitle(),
                'requested_new_title' => $requestedNewTitle,
                'title_adjusted' => null !== $requestedNewTitle && $requestedNewTitle !== $document->getTitle(),
                'language' => $resourceFile->getLanguage()?->getIsocode(),
                'size' => (int) ($resourceFile->getSize() ?? 0),
                'content_type' => 'text/html',
                'content_url' => $this->documentRepository->getResourceFileUrl(
                    $document,
                    ['cid' => $courseId],
                ),
            ],
        ];
    }
}
