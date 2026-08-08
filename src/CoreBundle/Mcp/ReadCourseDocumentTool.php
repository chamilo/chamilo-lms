<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Service\Html\TranslateHtmlLanguageService;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class ReadCourseDocumentTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseDocumentContentService $documentContentService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'read_course_document',
        description: 'Return an editable HTML document in a base course managed by the authenticated teacher, identified by documentId or by title. Modes: full (default) returns the complete HTML body; inventory returns present_languages / per_language sizes / content_sha256 without the body; source returns source_html for the source language only plus inventory. Prefer mode=source + upsert_course_document_language for iterative translation. Optional sourceLanguage overrides course/document language when selecting the source block.',
    )]
    public function readCourseDocument(
        int $courseId,
        ?int $documentId = null,
        ?string $title = null,
        string $mode = TranslateHtmlLanguageService::READ_MODE_FULL,
        ?string $sourceLanguage = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $document = $this->documentContentService->resolveDocument($course, $documentId, $title);

            return $this->documentContentService->readProjected($course, $document, $mode, $sourceLanguage);
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The document could not be read because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
