<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\CourseDescription\CourseDescriptionContentService;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class EditCourseDescriptionTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseDescriptionContentService $courseDescriptionContentService,
    ) {}

    /**
     * @return array{updated: true, changed_fields: list<string>}&array<string, mixed>
     */
    #[McpTool(
        name: 'edit_course_description',
        description: 'Edit an existing Course Description item in a base course managed by the authenticated teacher. Locate it by descriptionId, or by descriptionType for one of the 7 standard sections (descriptionType 8 / custom "Other" items require descriptionId, since a course can have several). You may replace content, rename via newTitle, and/or change language — pass an empty string for language to clear it. Call read_course_description first to read the current HTML before overwriting it. At least one of content, newTitle or language is required.',
    )]
    public function editCourseDescription(
        int $courseId,
        ?int $descriptionId = null,
        ?int $descriptionType = null,
        ?string $content = null,
        ?string $newTitle = null,
        ?string $language = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return $this->courseDescriptionContentService->edit(
                $context['course'],
                $descriptionId,
                $descriptionType,
                $content,
                $newTitle,
                $language,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course description could not be edited because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
