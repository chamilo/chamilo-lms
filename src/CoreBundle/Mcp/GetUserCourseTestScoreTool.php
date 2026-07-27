<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Exercise\UserCourseTestScoreProvider;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class GetUserCourseTestScoreTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private UserCourseTestScoreProvider $scoreProvider,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'get_user_course_test_score',
        description: 'Return the latest and best completed scores of a direct base-course student for a test managed by the authenticated teacher.',
    )]
    public function getUserCourseTestScore(
        int $courseId,
        int $testId,
        string $userIdentifier,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return $this->scoreProvider->provide(
                $context['course'],
                $testId,
                $userIdentifier,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The test score could not be loaded because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
