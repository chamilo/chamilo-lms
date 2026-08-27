<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Service\StudentSuccess\StudentLearningActivityCollector;
use Chamilo\CoreBundle\Service\StudentSuccess\StudentSuccessAnalysisStorage;
use Chamilo\CoreBundle\Service\StudentSuccess\StudentSuccessCoach;
use Chamilo\CoreBundle\Service\StudentSuccess\StudentSuccessPayloadBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted('ROLE_USER')]
#[Route('/ai/course/{courseId}/student-success')]
final class StudentSuccessController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
        private readonly AiFeatureAccessHelper $aiFeatureAccessHelper,
        private readonly AiProviderFactory $aiProviderFactory,
        private readonly StudentLearningActivityCollector $activityCollector,
        private readonly StudentSuccessPayloadBuilder $payloadBuilder,
        private readonly StudentSuccessAnalysisStorage $analysisStorage,
        private readonly StudentSuccessCoach $studentSuccessCoach,
    ) {}

    /**
     * Teacher-facing configuration used by the Vue modal.
     */
    #[Route(
        '/student/{userId}/configuration',
        name: 'chamilo_core_ai_student_success_configuration',
        methods: ['GET'],
    )]
    public function configuration(Request $request, int $courseId, int $userId): JsonResponse
    {
        $context = $this->resolveStudentContext($request, $courseId, $userId);
        if ($context['error'] instanceof JsonResponse) {
            return $context['error'];
        }

        /** @var Course $course */
        $course = $context['course'];
        /** @var Session|null $session */
        $session = $context['session'];
        /** @var User $student */
        $student = $context['student'];

        $providers = array_values(array_filter(array_map(
            static fn (mixed $provider): string => trim((string) $provider),
            $this->aiProviderFactory->getProvidersForType('text'),
        )));
        $storedStudentAnalysis = $this->analysisStorage->getStudentAnalysis($student, $course, $session);
        $storedCourseAnalysis = $this->analysisStorage->getCourseAnalysis($course, $session);
        $courseAnalysisProvider = trim((string) ($storedCourseAnalysis['metadata']['provider'] ?? ''));
        $defaultProvider = \in_array($courseAnalysisProvider, $providers, true)
            ? $courseAnalysisProvider
            : ($providers[0] ?? '');

        return $this->json([
            'success' => true,
            'providers' => $providers,
            'defaultProvider' => $defaultProvider,
            'courseAnalysisAvailable' => \is_array($storedCourseAnalysis['analysis'] ?? null)
                && [] !== $storedCourseAnalysis['analysis'],
            'previousAnalysis' => \is_array($storedStudentAnalysis) ? [
                'generatedAt' => $storedStudentAnalysis['generatedAt'] ?? null,
                'analysis' => \is_array($storedStudentAnalysis['analysis'] ?? null)
                    ? $storedStudentAnalysis['analysis']
                    : null,
                'metadata' => \is_array($storedStudentAnalysis['metadata'] ?? null)
                    ? $storedStudentAnalysis['metadata']
                    : [],
            ] : null,
        ]);
    }

    /**
     * Generate the reusable course/session analysis only when it does not
     * already exist. The UI calls this first so it can display a dedicated
     * "Analyzing course..." state before learner analysis begins.
     */
    #[Route(
        '/student/{userId}/prepare-course',
        name: 'chamilo_core_ai_student_success_prepare_course',
        methods: ['POST'],
    )]
    public function prepareCourse(Request $request, int $courseId, int $userId): JsonResponse
    {
        $context = $this->resolveStudentContext($request, $courseId, $userId);
        if ($context['error'] instanceof JsonResponse) {
            return $context['error'];
        }

        $teacher = $this->getUser();
        if (!$teacher instanceof User) {
            return $this->json(['success' => false, 'error' => 'Authentication is required.'], 401);
        }

        $provider = trim((string) $request->getPayload()->get('provider', ''));

        try {
            $result = $this->studentSuccessCoach->ensureCourseAnalysis(
                $context['course'],
                $context['session'],
                $teacher,
                '' !== $provider ? $provider : null,
            );

            return $this->json([
                'success' => true,
                'courseAnalysisAvailable' => true,
                ...$result,
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'success' => false,
                'error' => 'The course analysis could not be completed: '.$exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Run the actual Student Success AI Coach analysis and persist/send the
     * result locally after the privacy-filtered payload has been built.
     */
    #[Route(
        '/student/{userId}/analyze',
        name: 'chamilo_core_ai_student_success_analyze',
        methods: ['POST'],
    )]
    public function analyze(Request $request, int $courseId, int $userId): JsonResponse
    {
        $context = $this->resolveStudentContext($request, $courseId, $userId);
        if ($context['error'] instanceof JsonResponse) {
            return $context['error'];
        }

        $teacher = $this->getUser();
        if (!$teacher instanceof User) {
            return $this->json(['success' => false, 'error' => 'Authentication is required.'], 401);
        }

        $payload = $request->getPayload();
        $provider = trim((string) $payload->get('provider', ''));
        $teacherPrompt = trim((string) $payload->get('teacherPrompt', ''));
        if (mb_strlen($teacherPrompt) > 6000) {
            return $this->json([
                'success' => false,
                'error' => 'Additional instructions must be 6000 characters or fewer.',
            ], 422);
        }

        try {
            $result = $this->studentSuccessCoach->analyzeStudent(
                $context['course'],
                $context['session'],
                $context['student'],
                $teacher,
                $teacherPrompt,
                '' !== $provider ? $provider : null,
            );

            return $this->json([
                'success' => true,
                'localContext' => [
                    'userId' => $userId,
                    'courseId' => $courseId,
                    'sessionId' => (int) ($context['session']?->getId() ?? 0),
                ],
                ...$result,
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'success' => false,
                'error' => 'The learner analysis could not be completed: '.$exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Teacher-only diagnostic payload used to validate the activity collector
     * before any student data is sent to an AI provider.
     */
    #[Route(
        '/student/{userId}/activity',
        name: 'chamilo_core_ai_student_success_activity',
        methods: ['GET'],
    )]
    public function activity(Request $request, int $courseId, int $userId): JsonResponse
    {
        $context = $this->resolveStudentContext($request, $courseId, $userId);
        if ($context['error'] instanceof JsonResponse) {
            return $context['error'];
        }

        /** @var Course $course */
        $course = $context['course'];
        /** @var Session|null $session */
        $session = $context['session'];
        /** @var User $student */
        $student = $context['student'];
        $sessionId = (int) ($session?->getId() ?? 0);

        return $this->json([
            'success' => true,
            'localContext' => [
                // This identifier is returned only to the authenticated teacher
                // and is not part of the AI-bound activity payload.
                'userId' => $userId,
                'courseId' => $courseId,
                'sessionId' => $sessionId,
            ],
            'activity' => $this->activityCollector->collect($course, $session, $student),
        ]);
    }

    /**
     * Returns the exact privacy-filtered payload that is allowed to cross the
     * external AI boundary. This diagnostic endpoint does not call a provider.
     */
    #[Route(
        '/student/{userId}/ai-payload',
        name: 'chamilo_core_ai_student_success_payload',
        methods: ['GET'],
    )]
    public function aiPayload(Request $request, int $courseId, int $userId): JsonResponse
    {
        $context = $this->resolveStudentContext($request, $courseId, $userId);
        if ($context['error'] instanceof JsonResponse) {
            return $context['error'];
        }

        /** @var Course $course */
        $course = $context['course'];
        /** @var Session|null $session */
        $session = $context['session'];
        /** @var User $student */
        $student = $context['student'];

        return $this->json([
            'success' => true,
            'localContext' => [
                // Local-only routing metadata. It is intentionally outside
                // the payload that may later be sent to the AI provider.
                'userId' => $userId,
                'courseId' => $courseId,
                'sessionId' => (int) ($session?->getId() ?? 0),
            ],
            'payload' => $this->payloadBuilder->build($course, $session, $student),
        ]);
    }

    /**
     * @return array{course: Course|null, session: Session|null, student: User|null, error: JsonResponse|null}
     */
    private function resolveStudentContext(Request $request, int $courseId, int $userId): array
    {
        /** @var Course|null $course */
        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            return [
                'course' => null,
                'session' => null,
                'student' => null,
                'error' => $this->json(['success' => false, 'error' => 'Course not found.'], 404),
            ];
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        if (!$this->aiFeatureAccessHelper->isFeatureEnabledForCourse('course_analyser', $courseId)) {
            return [
                'course' => null,
                'session' => null,
                'student' => null,
                'error' => $this->json(['success' => false, 'error' => 'Course analyser is disabled for this course.'], 403),
            ];
        }

        $sessionId = max(0, $request->query->getInt('sid'));
        $session = null;
        if ($sessionId > 0) {
            /** @var Session|null $session */
            $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
            if (!$session instanceof Session || !$this->sessionContainsCourse($sessionId, $courseId)) {
                return [
                    'course' => null,
                    'session' => null,
                    'student' => null,
                    'error' => $this->json(['success' => false, 'error' => 'Invalid course-session context.'], 404),
                ];
            }
        }

        /** @var User|null $student */
        $student = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$student instanceof User || !$this->isStudentInContext($userId, $courseId, $sessionId)) {
            return [
                'course' => null,
                'session' => null,
                'student' => null,
                'error' => $this->json(['success' => false, 'error' => 'Learner not found in this course context.'], 404),
            ];
        }

        return [
            'course' => $course,
            'session' => $session,
            'student' => $student,
            'error' => null,
        ];
    }

    private function isStudentInContext(int $userId, int $courseId, int $sessionId): bool
    {
        if ($sessionId > 0) {
            return (bool) $this->connection->fetchOne(
                'SELECT 1
                   FROM session_rel_course_rel_user
                  WHERE user_id = :userId
                    AND c_id = :courseId
                    AND session_id = :sessionId
                    AND status = :studentStatus
                  LIMIT 1',
                [
                    'userId' => $userId,
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                    'studentStatus' => Session::STUDENT,
                ],
                [
                    'userId' => Types::INTEGER,
                    'courseId' => Types::INTEGER,
                    'sessionId' => Types::INTEGER,
                    'studentStatus' => Types::INTEGER,
                ],
            );
        }

        return (bool) $this->connection->fetchOne(
            'SELECT 1
               FROM course_rel_user
              WHERE user_id = :userId
                AND c_id = :courseId
                AND status = :studentStatus
              LIMIT 1',
            [
                'userId' => $userId,
                'courseId' => $courseId,
                'studentStatus' => CourseRelUser::STUDENT,
            ],
            [
                'userId' => Types::INTEGER,
                'courseId' => Types::INTEGER,
                'studentStatus' => Types::INTEGER,
            ],
        );
    }

    private function sessionContainsCourse(int $sessionId, int $courseId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1
               FROM session_rel_course
              WHERE session_id = :sessionId
                AND c_id = :courseId
              LIMIT 1',
            [
                'sessionId' => $sessionId,
                'courseId' => $courseId,
            ],
            [
                'sessionId' => Types::INTEGER,
                'courseId' => Types::INTEGER,
            ],
        );
    }
}
