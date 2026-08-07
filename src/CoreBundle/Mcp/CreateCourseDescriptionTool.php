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

final readonly class CreateCourseDescriptionTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseDescriptionContentService $courseDescriptionContentService,
    ) {}

    /**
     * @return array{created: bool, updated_existing: bool}&array<string, mixed>
     */
    #[McpTool(
        name: 'create_course_description',
        description: 'Create a Course Description item in a base course managed by the authenticated teacher. descriptionType must be one of: 1 Description, 2 Objectives, 3 Topics, 4 Methodology, 5 Course material, 6 Resources, 7 Assessment, 8 Other (custom). For types 1-7 there is only one item per type in a course: calling this again with the same descriptionType updates the existing item in place (created=false, updated_existing=true) instead of duplicating it. Type 8 always creates a new item, since a course can have several custom items. Call get_course_description_template first for guiding questions and existence metadata, and read_course_description to inspect existing HTML before overwriting. Write the HTML content in the same style conventions as create_course_document. If language is omitted, no specific language is set.',
    )]
    public function createCourseDescription(
        int $courseId,
        int $descriptionType,
        string $title,
        string $content,
        ?string $language = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return $this->courseDescriptionContentService->createOrUpdate(
                $context['course'],
                $descriptionType,
                $title,
                $content,
                $language,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course description could not be created because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
