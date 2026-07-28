<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Assignment\McpCourseAssignmentCreator;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class CreateCourseAssignmentTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private McpCourseAssignmentCreator $assignmentCreator,
    ) {}

    /**
     * @return array{created: true, assignment: array<string, mixed>}
     */
    #[McpTool(
        name: 'create_course_assignment',
        description: 'Create an assignment with a description and maximum score in a base course managed by the authenticated teacher. The assignment is a draft unless publish is true.',
    )]
    public function createCourseAssignment(
        int $courseId,
        string $title,
        string $description,
        float $maximumScore,
        bool $publish = false,
        int $submissionMode = 0,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return [
                'created' => true,
                'assignment' => $this->assignmentCreator->create(
                    $context['course'],
                    $context['user'],
                    $title,
                    $description,
                    $maximumScore,
                    $publish,
                    $submissionMode,
                ),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The assignment could not be created because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
