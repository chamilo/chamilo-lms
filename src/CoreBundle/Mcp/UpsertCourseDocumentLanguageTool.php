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

final readonly class UpsertCourseDocumentLanguageTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseDocumentContentService $documentContentService,
    ) {}

    /**
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    #[McpTool(
        name: 'upsert_course_document_language',
        description: 'Add or replace ONE language variant inside an existing HTML course document without rewriting the full multi-language body. Locate the document by documentId or exact title. Call read_course_document with mode=source (or inventory) first, translate offline, then pass language + INNER HTML only for that language. mode is upsert (default), create_only or replace_only. Optional ifMatchSha256 rejects stale concurrent writes. Optional sourceLanguage controls wrapping of unmarked content. Returns inventory metadata, not the full HTML body. Prefer this over edit_course_document for translation workflows.',
    )]
    public function upsertCourseDocumentLanguage(
        int $courseId,
        string $language,
        string $content,
        ?int $documentId = null,
        ?string $title = null,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $document = $this->documentContentService->resolveDocument($course, $documentId, $title);

            return $this->documentContentService->upsertLanguage(
                $course,
                $document,
                $language,
                $content,
                $mode,
                $sourceLanguage,
                $ifMatchSha256,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The document language variant could not be saved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
