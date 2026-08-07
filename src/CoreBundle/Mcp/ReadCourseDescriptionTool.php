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
     *     items: list<array<string, mixed>>
     * }
     */
    #[McpTool(
        name: 'read_course_description',
        description: 'Read the current HTML content of Course Description sections in a base course managed by the authenticated teacher. Without filters, returns every existing section (standard types 1-7 in order, then custom "Other" items) with description_id, description_type, type_label, title, content, word_count and language. Pass descriptionId, or descriptionType for one of the 7 standard sections, to read a single item (descriptionType 8 / custom items require descriptionId, since a course can have several). Use this before edit_course_description to inspect what is already written. get_course_description_template only reports whether a section exists (title/word count), not its body — call this tool when you need the actual content.',
    )]
    public function readCourseDescription(
        int $courseId,
        ?int $descriptionId = null,
        ?int $descriptionType = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return $this->courseDescriptionContentService->read(
                $context['course'],
                $descriptionId,
                $descriptionType,
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
