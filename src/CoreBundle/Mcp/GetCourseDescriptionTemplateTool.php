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

final readonly class GetCourseDescriptionTemplateTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseDescriptionContentService $courseDescriptionContentService,
    ) {}

    /**
     * @return array{
     *     course_id: int,
     *     custom_type: int,
     *     sections: list<array<string, mixed>>,
     *     custom_items: list<array<string, mixed>>
     * }
     */
    #[McpTool(
        name: 'get_course_description_template',
        description: 'Get the Course Description template for a base course managed by the authenticated teacher: the 7 standard sections (Description, Objectives, Topics, Methodology, Course material, Resources, Assessment) each with its descriptionType, a guiding question, and whether it already has content (with its id/title/word count/language if so — not the HTML body). Also lists any existing custom ("Other", descriptionType 8) items. Call read_course_description to load the actual HTML of filled sections. Ask the user the guiding question for each section they want to fill in, then call create_course_description or edit_course_description. Write content as clean semantic HTML (headings, paragraphs, lists) in the same style used by create_course_document — it is sanitized with the same security profile as course documents.',
    )]
    public function getCourseDescriptionTemplate(int $courseId): array
    {
        try {
            $context = $this->courseContext->resolve($courseId);

            return $this->courseDescriptionContentService->getTemplate($context['course']);
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course description template could not be retrieved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
