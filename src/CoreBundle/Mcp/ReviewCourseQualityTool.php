<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Course\McpCourseQualityReviewer;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class ReviewCourseQualityTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private McpCourseQualityReviewer $courseReviewer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'review_course_quality',
        description: 'Analyze a teacher-owned base course, including published, pending and draft learning paths, full readable document text, tests, assignments and surveys, and return concise evidence-based improvement recommendations.',
    )]
    public function reviewCourseQuality(
        int $courseId,
        ?string $focus = null,
        ?string $provider = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return $this->courseReviewer->review(
                $context['course'],
                $context['user'],
                $focus,
                $provider,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException(
                'The course review could not be generated because of an unexpected server error. Check the Chamilo log for technical details.',
                0,
                $throwable,
            );
        }
    }
}
