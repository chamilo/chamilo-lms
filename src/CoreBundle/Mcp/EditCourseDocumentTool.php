<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
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
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @return array{
     *     updated: true,
     *     document: array{
     *         document_id: int,
     *         title: string,
     *         size: int,
     *         content_type: 'text/html',
     *         content_url: string
     *     }
     * }
     */
    #[McpTool(
        name: 'edit_course_document',
        description: 'Replace the HTML content of an existing editable document in a base course managed by the authenticated teacher, identified by documentId or by title. Call read_course_document first to see the current content, then submit the full updated HTML here — this replaces the whole document content, not a partial patch.',
    )]
    public function editCourseDocument(
        int $courseId,
        string $content,
        ?int $documentId = null,
        ?string $title = null,
    ): array {
        try {
            return $this->doEditCourseDocument($courseId, $content, $documentId, $title);
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The document could not be edited because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{
     *     updated: true,
     *     document: array{
     *         document_id: int,
     *         title: string,
     *         size: int,
     *         content_type: 'text/html',
     *         content_url: string
     *     }
     * }
     */
    private function doEditCourseDocument(
        int $courseId,
        string $content,
        ?int $documentId,
        ?string $title,
    ): array {
        $context = $this->courseContext->resolve($courseId);
        $course = $context['course'];
        $user = $context['user'];

        $document = $this->documentContentService->resolveDocument($course, $documentId, $title);
        $this->documentContentService->assertEditableHtmlDocument($document);

        $content = trim($content);
        if ('' === $content) {
            throw new InvalidArgumentException('The new document content is required.');
        }

        if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
            throw new InvalidArgumentException('The new document content is too large.');
        }

        $content = $this->documentContentService->sanitizeHtml($content);
        if ('' === trim(strip_tags($content))) {
            throw new InvalidArgumentException('The new document content is empty after sanitization.');
        }

        $this->documentContentService->writeContent($document, $content);

        $resolvedDocumentId = (int) ($document->getIid() ?? 0);

        if (!$user instanceof User || null === $user->getId()) {
            throw new RuntimeException('The authenticated user could not be resolved.');
        }

        $this->aiDisclosureHelper->markAiAssistedExtraField('document', $resolvedDocumentId, true);
        $this->aiDisclosureHelper->logAudit(
            targetKey: 'course:'.$courseId.':document:'.$resolvedDocumentId.':edit',
            userId: (int) $user->getId(),
            meta: ['feature' => 'edit_course_document'],
            courseId: $courseId,
        );

        $resourceFile = $document->getResourceNode()?->getFirstResourceFile();

        return [
            'updated' => true,
            'document' => [
                'document_id' => $resolvedDocumentId,
                'title' => $document->getTitle(),
                'size' => (int) ($resourceFile?->getSize() ?? 0),
                'content_type' => 'text/html',
                'content_url' => $this->documentRepository->getResourceFileUrl(
                    $document,
                    ['cid' => $courseId],
                ),
            ],
        ];
    }
}
