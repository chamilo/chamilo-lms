<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeReport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Read-only provider for the migrated exercise learner attempts report.
 *
 * @implements ProviderInterface<ExerciseRuntimeReport>
 */
final readonly class ExerciseRuntimeReportProvider implements ProviderInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const STATUS_PENDING_CORRECTION = 'pending_correction';
    private const STATUS_COMPLETED = 'completed';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeReport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to view this exercise report.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $attempts = $this->getAttempts($request, $quiz, $course, $session);

        $response = new ExerciseRuntimeReport();
        $response->exerciseId = $exerciseId;
        $response->title = $quiz->getTitle();
        $response->description = (string) $quiz->getDescription();
        $response->attempts = $attempts;
        $response->filters = [
            'firstName' => trim((string) $request->query->get('firstName', '')),
            'lastName' => trim((string) $request->query->get('lastName', '')),
            'status' => trim((string) $request->query->get('status', '')),
        ];
        $response->legacyUrls = $this->getLegacyUrls($quiz, $course, $session, $request);
        $response->totalItems = \count($attempts);
        $response->canManage = true;

        return $response;
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz.iid')
            ->addSelect('links.visibility AS linkVisibility')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(links.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $row = $queryBuilder->getQuery()->getOneOrNullResult();
        if (null === $row) {
            throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
        }

        $visibility = \is_array($row) ? (int) ($row['linkVisibility'] ?? 0) : 0;
        if (0 !== $visibility && self::VISIBILITY_PUBLISHED !== $visibility && !$this->canManageExercises()) {
            throw new AccessDeniedHttpException('The requested exercise is not visible.');
        }

        return $quiz;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAttempts(Request $request, CQuiz $quiz, Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt', 'user')
            ->from(TrackEExercise::class, 'attempt')
            ->innerJoin('attempt.user', 'user')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('attempt.exeDate', 'DESC')
            ->addOrderBy('attempt.exeId', 'DESC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $firstName = trim((string) $request->query->get('firstName', ''));
        if ('' !== $firstName) {
            $queryBuilder
                ->andWhere('LOWER(user.firstname) LIKE :firstName')
                ->setParameter('firstName', '%'.mb_strtolower($firstName).'%', Types::STRING)
            ;
        }

        $lastName = trim((string) $request->query->get('lastName', ''));
        if ('' !== $lastName) {
            $queryBuilder
                ->andWhere('LOWER(user.lastname) LIKE :lastName')
                ->setParameter('lastName', '%'.mb_strtolower($lastName).'%', Types::STRING)
            ;
        }

        $status = trim((string) $request->query->get('status', ''));
        if (self::STATUS_PENDING_CORRECTION === $status) {
            $queryBuilder->andWhere("attempt.questionsToCheck <> ''");
        } elseif (self::STATUS_INCOMPLETE === $status) {
            $queryBuilder
                ->andWhere('attempt.status = :status')
                ->setParameter('status', self::STATUS_INCOMPLETE, Types::STRING)
            ;
        } elseif (self::STATUS_COMPLETED === $status) {
            $queryBuilder
                ->andWhere('attempt.status = :status')
                ->andWhere("attempt.questionsToCheck = ''")
                ->setParameter('status', self::STATUS_COMPLETED, Types::STRING)
            ;
        }

        $attempts = [];
        foreach ($queryBuilder->getQuery()->getResult() as $attempt) {
            if (!$attempt instanceof TrackEExercise) {
                continue;
            }

            $attempts[] = $this->normalizeAttempt($attempt, $quiz, $request);
        }

        return $attempts;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAttempt(TrackEExercise $attempt, CQuiz $quiz, Request $request): array
    {
        $user = $attempt->getUser();
        $questionsToCheck = $this->parseQuestionIds($attempt->getQuestionsToCheck());
        $pendingCorrection = [] !== $questionsToCheck;
        $status = $this->getAttemptStatus($attempt, $pendingCorrection);
        $statusLabel = match ($status) {
            self::STATUS_PENDING_CORRECTION => 'Pending correction',
            self::STATUS_INCOMPLETE => 'Ongoing',
            default => 'Completed',
        };
        $score = $attempt->getScore();
        $maxScore = $attempt->getMaxScore();
        $percentage = 0.0 < $maxScore ? round(($score * 100) / $maxScore, 2) : 0.0;
        $baseParams = $this->getBaseParams((int) $quiz->getIid(), $request);
        $attemptId = (int) $attempt->getExeId();
        $userId = (int) $user->getId();

        return [
            'id' => $attemptId,
            'attemptId' => $attemptId,
            'exerciseId' => (int) $quiz->getIid(),
            'userId' => $userId,
            'username' => $user->getUsername(),
            'firstName' => (string) $user->getFirstname(),
            'lastName' => (string) $user->getLastname(),
            'fullName' => $user->getFullName(),
            'groupName' => '-',
            'duration' => $attempt->getExeDuration(),
            'startedAt' => $this->formatDate($attempt->getStartDate()),
            'completedAt' => $this->formatDate($attempt->getExeDate()),
            'score' => $score,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'ip' => $attempt->getUserIp(),
            'status' => $status,
            'statusLabel' => $statusLabel,
            'pendingCorrection' => $pendingCorrection,
            'questionsToCheck' => $questionsToCheck,
            'learningPath' => $this->formatLearningPath($attempt),
            'canReview' => self::STATUS_INCOMPLETE !== $status,
            'canDelete' => true,
            'legacyUrls' => [
                'result' => api_get_path(WEB_CODE_PATH).'exercise/exercise_result.php?'.http_build_query(['exe_id' => $attemptId] + $baseParams),
                'pdf' => api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.http_build_query(['action' => 'export_pdf', 'attemptId' => $attemptId, 'userId' => $userId] + $baseParams),
                'sendEmail' => api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.http_build_query(['action' => 'send_email', 'attemptId' => $attemptId] + $baseParams),
                'recalculate' => api_get_path(WEB_CODE_PATH).'exercise/recalculate.php?'.http_build_query(['id' => $attemptId, 'exercise' => (int) $quiz->getIid(), 'user' => $userId] + $this->getContextParams($request)),
            ],
        ];
    }

    private function getAttemptStatus(TrackEExercise $attempt, bool $pendingCorrection): string
    {
        if ($pendingCorrection) {
            return self::STATUS_PENDING_CORRECTION;
        }

        if (self::STATUS_INCOMPLETE === (string) $attempt->getStatus()) {
            return self::STATUS_INCOMPLETE;
        }

        return self::STATUS_COMPLETED;
    }

    private function formatLearningPath(TrackEExercise $attempt): string
    {
        if (0 < $attempt->getOrigLpId()) {
            return '#'.$attempt->getOrigLpId();
        }

        return '-';
    }

    private function formatDate(DateTimeInterface $date): string
    {
        return $date->format(DateTimeInterface::ATOM);
    }

    /**
     * @return array<int, int>
     */
    private function parseQuestionIds(string $value): array
    {
        $ids = [];
        foreach (preg_split('/[,;]+/', $value) ?: [] as $rawId) {
            $id = (int) trim($rawId);
            if (0 < $id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<string, string>
     */
    private function getLegacyUrls(CQuiz $quiz, Course $course, ?Session $session, Request $request): array
    {
        $baseParams = $this->getBaseParams((int) $quiz->getIid(), $request);
        $contextParams = $this->getContextParams($request);

        return [
            'legacyReport' => api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.http_build_query($baseParams),
            'backToQuestions' => api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.http_build_query($contextParams),
            'liveStats' => api_get_path(WEB_CODE_PATH).'exercise/live_stats.php?'.http_build_query($baseParams),
            'questionReport' => api_get_path(WEB_CODE_PATH).'exercise/stats.php?'.http_build_query($baseParams),
            'questionStats' => api_get_path(WEB_CODE_PATH).'exercise/question_stats.php?'.http_build_query(['id' => (int) $quiz->getIid()] + $contextParams),
            'export' => api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.http_build_query(['export_report' => 1] + $baseParams),
            'recalculateAll' => api_get_path(WEB_CODE_PATH).'exercise/recalculate_all.php?'.http_build_query(['exercise' => (int) $quiz->getIid()] + $contextParams),
            'exportAllPdf' => api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.http_build_query(['action' => 'export_all_results'] + $baseParams),
            'sendAllEmails' => api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.http_build_query(['action' => 'send_all_emails'] + $baseParams),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function getBaseParams(int $exerciseId, Request $request): array
    {
        return ['exerciseId' => $exerciseId] + $this->getContextParams($request);
    }

    /**
     * @return array<string, int|string>
     */
    private function getContextParams(Request $request): array
    {
        $params = [
            'cid' => $request->query->getInt('cid'),
            'gid' => $request->query->getInt('gid'),
        ];

        $sessionId = $request->query->getInt('sid');
        if (0 < $sessionId) {
            $params['sid'] = $sessionId;
        }

        return $params;
    }
}
