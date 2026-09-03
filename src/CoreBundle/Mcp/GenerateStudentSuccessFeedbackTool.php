<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CoreBundle\Service\StudentSuccess\StudentSuccessCoach;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class GenerateStudentSuccessFeedbackTool
{
    private const int MAX_TEACHER_PROMPT_LENGTH = 6000;

    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private EntityManagerInterface $entityManager,
        private AiFeatureAccessHelper $aiFeatureAccessHelper,
        private StudentSuccessCoach $studentSuccessCoach,
    ) {}

    /**
     * Generate the same Student Success AI feedback available from the teacher
     * reporting UI. The AI-bound learner payload remains anonymized by the
     * existing StudentSuccessCoach/StudentSuccessPayloadBuilder flow.
     *
     * @return array{
     *     scope: 'base_course'|'course_session',
     *     context: array{course_id:int, session_id:int, learner_user_id:int},
     *     provider:string,
     *     courseAnalysisGenerated:bool,
     *     payloadCompacted:bool,
     *     analysis:array<string,mixed>,
     *     messageSent:bool,
     *     messageId:int|null
     * }
     */
    #[McpTool(
        name: 'generate_student_success_feedback',
        description: 'Generate Student Success AI feedback for a learner in a course managed by the authenticated teacher. Reuses the same privacy-filtered learner activity, reusable course analysis, course-language pedagogical sources, stored analysis and Messages delivery as the Chamilo Student Success UI. Use sessionId only when the learner must be analyzed inside a specific course session.',
    )]
    public function generateStudentSuccessFeedback(
        int $courseId,
        int $userId,
        int $sessionId = 0,
        string $teacherPrompt = '',
        ?string $provider = null,
    ): array {
        try {
            return $this->doGenerateStudentSuccessFeedback(
                $courseId,
                $userId,
                $sessionId,
                $teacherPrompt,
                $provider,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException(
                'The Student Success feedback could not be generated because of an unexpected server error. Check the Chamilo log for technical details.',
                0,
                $throwable,
            );
        }
    }

    /**
     * @return array{
     *     scope: 'base_course'|'course_session',
     *     context: array{course_id:int, session_id:int, learner_user_id:int},
     *     provider:string,
     *     courseAnalysisGenerated:bool,
     *     payloadCompacted:bool,
     *     analysis:array<string,mixed>,
     *     messageSent:bool,
     *     messageId:int|null
     * }
     */
    private function doGenerateStudentSuccessFeedback(
        int $courseId,
        int $userId,
        int $sessionId,
        string $teacherPrompt,
        ?string $provider,
    ): array {
        if ($userId <= 0) {
            throw new InvalidArgumentException('The learner user ID must be a positive integer.');
        }

        if ($sessionId < 0) {
            throw new InvalidArgumentException('The session ID cannot be negative.');
        }

        $teacherPrompt = trim($teacherPrompt);
        if (mb_strlen($teacherPrompt) > self::MAX_TEACHER_PROMPT_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Additional instructions must be %d characters or fewer.', self::MAX_TEACHER_PROMPT_LENGTH),
            );
        }

        $provider = null === $provider ? null : trim($provider);
        if ('' === $provider) {
            $provider = null;
        }

        $resolved = $this->courseContext->resolve($courseId);
        $course = $resolved['course'];
        $teacher = $resolved['user'];

        if (!$this->aiFeatureAccessHelper->isFeatureEnabledForCourse('course_analyser', $courseId)) {
            throw new AccessDeniedException('Course analyser is disabled for this course.');
        }

        /** @var User|null $student */
        $student = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$student instanceof User) {
            throw new InvalidArgumentException('The learner was not found.');
        }

        $session = null;
        if ($sessionId > 0) {
            /** @var Session|null $session */
            $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
            if (!$session instanceof Session || null === $session->getCourseSubscription($course)) {
                throw new InvalidArgumentException('Invalid course-session context.');
            }

            if (!$session->hasUserInCourse($student, $course, Session::STUDENT)) {
                throw new AccessDeniedException('The learner is not registered as a student in this course-session context.');
            }
        } else {
            $courseSubscription = $this->entityManager->getRepository(CourseRelUser::class)->findOneBy([
                'course' => $course,
                'user' => $student,
                'status' => CourseRelUser::STUDENT,
            ]);

            if (!$courseSubscription instanceof CourseRelUser) {
                throw new AccessDeniedException('The learner is not registered as a student in this base course.');
            }
        }

        $result = $this->studentSuccessCoach->analyzeStudent(
            $course,
            $session,
            $student,
            $teacher,
            $teacherPrompt,
            $provider,
        );

        return [
            'scope' => $session instanceof Session ? 'course_session' : 'base_course',
            'context' => [
                'course_id' => (int) $course->getId(),
                'session_id' => (int) ($session?->getId() ?? 0),
                'learner_user_id' => (int) $student->getId(),
            ],
            ...$result,
        ];
    }
}
