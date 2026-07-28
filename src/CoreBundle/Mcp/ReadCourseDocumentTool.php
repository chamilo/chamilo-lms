<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

use const DATE_ATOM;

final readonly class ReadCourseDocumentTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseDocumentContentService $documentContentService,
    ) {}

    /**
     * @return array{
     *     document_id: int,
     *     title: string,
     *     content: string,
     *     content_type: 'text/html',
     *     size: int,
     *     language: string|null,
     *     modified_at: string|null
     * }
     */
    #[McpTool(
        name: 'read_course_document',
        description: 'Return the current HTML content, title and metadata of an editable document in a base course managed by the authenticated teacher, identified by documentId or by title. Call this before edit_course_document to see what to change.',
    )]
    public function readCourseDocument(
        int $courseId,
        ?int $documentId = null,
        ?string $title = null,
    ): array {
        try {
            return $this->doReadCourseDocument($courseId, $documentId, $title);
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The document could not be read because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{
     *     document_id: int,
     *     title: string,
     *     content: string,
     *     content_type: 'text/html',
     *     size: int,
     *     language: string|null,
     *     modified_at: string|null
     * }
     */
    private function doReadCourseDocument(
        int $courseId,
        ?int $documentId,
        ?string $title,
    ): array {
        $context = $this->courseContext->resolve($courseId);
        $course = $context['course'];

        $document = $this->documentContentService->resolveDocument($course, $documentId, $title);
        $this->documentContentService->assertEditableHtmlDocument($document);

        $content = $this->documentContentService->readContent($document);
        $resourceFile = $document->getResourceNode()?->getFirstResourceFile();

        return [
            'document_id' => (int) $document->getIid(),
            'title' => $document->getTitle(),
            'content' => $content,
            'content_type' => 'text/html',
            'size' => (int) ($resourceFile?->getSize() ?? 0),
            'language' => $resourceFile?->getLanguage()?->getIsocode(),
            'modified_at' => $resourceFile?->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}
