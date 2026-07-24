<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Forum\RecentCourseForumActivityProvider;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class FindRecentCourseForumActivityTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private RecentCourseForumActivityProvider $activityProvider,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'find_recent_course_forum_activity',
        description: 'Find visible recent forum posts related to a topic in a base course managed by the authenticated teacher.',
    )]
    public function findRecentCourseForumActivity(
        int $courseId,
        string $topic,
        int $days = 30,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return $this->activityProvider->provide(
                $context['course'],
                $topic,
                $days,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The forum activity could not be searched because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
