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

final readonly class ReadCourseDescriptionTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseDescriptionContentService $courseDescriptionContentService,
    ) {}

    /**
     * @return array{
     *     course_id: int,
     *     total: int,
     *     mode: string,
     *     items: list<array<string, mixed>>
     * }
     */
    #[McpTool(
        name: 'read_course_description',
        description: 'Read Course Description sections in a base course managed by the authenticated teacher. Without filters, returns every existing section (standard types 1-7 in order, then custom "Other" items). Pass descriptionId, or descriptionType for one of the 7 standard sections, to read a single item (descriptionType 8 / custom items require descriptionId). Modes: full (default) returns the full HTML body; inventory returns present_languages / per_language sizes / content_sha256 without the body (cheap for translation planning); source returns source_html for the source language only plus inventory (use this before translating — do not pull the growing multi-lang blob). Optional sourceLanguage overrides the course language used to pick the source block. Prefer mode=source + upsert_course_description_language for iterative translation; use edit_course_description only when replacing the whole document.',
    )]
    public function readCourseDescription(
        int $courseId,
        ?int $descriptionId = null,
        ?int $descriptionType = null,
        string $mode = TranslateHtmlLanguageService::READ_MODE_FULL,
        ?string $sourceLanguage = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return $this->courseDescriptionContentService->read(
                $context['course'],
                $descriptionId,
                $descriptionType,
                $mode,
                $sourceLanguage,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course description could not be read because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
