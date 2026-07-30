<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Assignment\McpCourseAssignmentCreator;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use DateInterval;
use DateTime;
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
     * @return array{created: bool, assignment: array<string, mixed>}
     */
    #[McpTool(
        name: 'create_course_assignment',
        description: 'Create an assignment with a description, maximum score and optional real due date in a base course managed by the authenticated teacher. Use dueAt as an ISO 8601 date or dueInDays for a relative deadline. Repeated calls with the same course and exact title reuse the existing assignment instead of creating a duplicate. On reuse, dueInDays preserves an existing deadline while an explicit dueAt can update it. The assignment is a draft unless publish is true.',
    )]
    public function createCourseAssignment(
        int $courseId,
        string $title,
        string $description,
        float $maximumScore,
        bool $publish = false,
        int $submissionMode = 0,
        ?string $dueAt = null,
        ?int $dueInDays = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $dueDate = $this->resolveDueDate($dueAt, $dueInDays);
            $preserveExistingDueDate = null !== $dueInDays;
            $assignment = $this->assignmentCreator->create(
                $context['course'],
                $context['user'],
                $title,
                $description,
                $maximumScore,
                $publish,
                $submissionMode,
                $dueDate,
                $preserveExistingDueDate,
            );

            return [
                'created' => (bool) ($assignment['created'] ?? false),
                'assignment' => $assignment,
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The assignment could not be created because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    private function resolveDueDate(?string $dueAt, ?int $dueInDays): ?DateTime
    {
        $dueAt = null !== $dueAt ? trim($dueAt) : '';

        if ('' !== $dueAt && null !== $dueInDays) {
            throw new InvalidArgumentException('Provide dueAt or dueInDays, not both.');
        }

        if (null !== $dueInDays) {
            if ($dueInDays < 1 || $dueInDays > 3650) {
                throw new InvalidArgumentException('The dueInDays value must be between 1 and 3650.');
            }

            return (new DateTime())->add(new DateInterval('P'.$dueInDays.'D'));
        }

        if ('' === $dueAt) {
            return null;
        }

        try {
            return new DateTime($dueAt);
        } catch (Throwable) {
            throw new InvalidArgumentException('The dueAt value must be a valid ISO 8601 date and time.');
        }
    }
}
