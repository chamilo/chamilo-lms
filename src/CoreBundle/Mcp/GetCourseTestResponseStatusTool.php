<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Service\Exercise\CourseTestResponseStatusProvider;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class GetCourseTestResponseStatusTool
{
    public function __construct(
        private Security $security,
        private AccessUrlHelper $accessUrlHelper,
        private CourseRelUserRepository $courseRelUserRepository,
        private CQuizRepository $quizRepository,
        private CourseTestResponseStatusProvider $statusProvider,
    ) {}

    /**
     * @return array{
     *     scope: 'base_course',
     *     course: array{course_id: int, title: string},
     *     test: array{quiz_id: int, title: string},
     *     students: array{
     *         total_students: int,
     *         answered_students: int,
     *         pending_students: int,
     *         in_progress_students: int,
     *         not_started_students: int,
     *         response_rate_percent: float
     *     },
     *     attempts: array{
     *         completed_attempts: int,
     *         incomplete_attempts: int,
     *         considered_attempts: int,
     *         ignored_non_student_attempts: int
     *     }
     * }
     */
    #[McpTool(
        name: 'get_course_test_response_status',
        description: 'Return response status for a test in a base course managed by the authenticated teacher. Answered students have at least one completed attempt. Students with only incomplete attempts remain pending and are reported as in progress.',
    )]
    public function getCourseTestResponseStatus(int $courseId, int $testId): array
    {
        try {
            return $this->doGetCourseTestResponseStatus($courseId, $testId);
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course test response status could not be loaded because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{
     *     scope: 'base_course',
     *     course: array{course_id: int, title: string},
     *     test: array{quiz_id: int, title: string},
     *     students: array{
     *         total_students: int,
     *         answered_students: int,
     *         pending_students: int,
     *         in_progress_students: int,
     *         not_started_students: int,
     *         response_rate_percent: float
     *     },
     *     attempts: array{
     *         completed_attempts: int,
     *         incomplete_attempts: int,
     *         considered_attempts: int,
     *         ignored_non_student_attempts: int
     *     }
     * }
     */
    private function doGetCourseTestResponseStatus(int $courseId, int $testId): array
    {
        if ($courseId <= 0) {
            throw new InvalidArgumentException('The course ID must be a positive integer.');
        }

        if ($testId <= 0) {
            throw new InvalidArgumentException('The test ID must be a positive integer.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('An authenticated Chamilo user is required.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new RuntimeException('The current Chamilo access URL could not be resolved.');
        }

        $course = $this->courseRelUserRepository->findTeacherCourseForUserAndAccessUrl(
            $user,
            $accessUrl,
            $courseId,
        );
        if (null === $course) {
            throw new AccessDeniedException('The course was not found or is not managed by the authenticated teacher.');
        }

        $quiz = $this->quizRepository->find($testId);
        if (!$quiz instanceof CQuiz) {
            throw new InvalidArgumentException('The test was not found.');
        }

        $quizLink = $quiz->getResourceNode()?->getResourceLinkByContext($course, null, null);
        if (!$quizLink instanceof ResourceLink) {
            throw new AccessDeniedException('The test does not belong to the selected base course.');
        }

        return $this->statusProvider->getBaseCourseStatus($course, $quiz);
    }
}
