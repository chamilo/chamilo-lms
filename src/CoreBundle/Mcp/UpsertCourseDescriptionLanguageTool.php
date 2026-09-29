<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\CourseDescription\CourseDescriptionContentService;
use Chamilo\CoreBundle\Service\Html\TranslateHtmlLanguageService;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

/**
 * Alternate write path for course descriptions: upsert one translatehtml
 * language block without resending the full multi-language HTML document.
 */
final readonly class UpsertCourseDescriptionLanguageTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseDescriptionContentService $courseDescriptionContentService,
    ) {}

    /**
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    #[McpTool(
        name: 'upsert_course_description_language',
        description: 'Add or replace ONE language variant inside an existing Course Description HTML body, without rewriting the full multi-language document. Use this when translating content iteratively: first call read_course_description with mode=source (or mode=inventory) to learn present languages and obtain the source HTML only, translate offline, then call this tool with language + the INNER HTML for that language only (no outer mce-translatehtml wrapper required — the server wraps it). Locate the item by descriptionId, or by descriptionType for one of the 7 standard sections (custom type 8 requires descriptionId). mode is upsert (default), create_only (fail if language exists), or replace_only (fail if missing). Optional ifMatchSha256 (from a prior read) rejects stale concurrent writes. Optional sourceLanguage controls how unmarked content is wrapped when adding the first second language (defaults to the course language). Returns inventory metadata (present_languages, content_sha256, per_language) and does NOT return the full HTML body — call read mode=full only when you need it. Prefer this over edit_course_description for translation workflows.',
    )]
    public function upsertCourseDescriptionLanguage(
        int $courseId,
        string $language,
        string $content,
        ?int $descriptionId = null,
        ?int $descriptionType = null,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return $this->courseDescriptionContentService->upsertLanguage(
                $context['course'],
                $descriptionId,
                $descriptionType,
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
            throw new ToolCallException('The course description language variant could not be saved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
