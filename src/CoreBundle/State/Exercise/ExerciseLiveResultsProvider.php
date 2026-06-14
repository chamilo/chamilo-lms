<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseLiveResults;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateInterval;
use DateTime;
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
 * Read-only provider for the migrated exercise live results page.
 *
 * Legacy live_stats.php refreshes jqGrid every 10 seconds and asks for the
 * learners taking the exercise in the last minutes. This provider keeps the
 * same read-only scope using track_e_exercises and track_e_attempt, without
 * touching tracking, grades, gradebook or attempt status.
 *
 * @implements ProviderInterface<ExerciseLiveResults>
 */
final readonly class ExerciseLiveResultsProvider implements ProviderInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_ALL = 'all';
    private const DEFAULT_MINUTES = 60;
    private const MIN_MINUTES = 1;
    private const MAX_MINUTES = 1440;

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseLiveResults
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to view live exercise results.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $minutes = $this->getMinutes($request);
        $status = $this->getStatusFilter($request);
        $attempts = $this->getLiveAttempts($quiz, $course, $session, $minutes, $status);

        $response = new ExerciseLiveResults();
        $response->exerciseId = $exerciseId;
        $response->title = $quiz->getTitle();
        $response->description = (string) $quiz->getDescription();
        $response->attempts = $attempts;
        $response->summary = $this->buildSummary($attempts, $minutes);
        $response->filters = [
            'minutes' => $minutes,
            'status' => $status,
        ];
        $response->legacyUrls = [];
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

    private function getMinutes(Request $request): int
    {
        $minutes = $request->query->getInt('minutes', self::DEFAULT_MINUTES);

        return max(self::MIN_MINUTES, min(self::MAX_MINUTES, $minutes));
    }

    private function getStatusFilter(Request $request): string
    {
        $status = trim((string) $request->query->get('status', self::STATUS_ALL));

        return \in_array($status, [self::STATUS_ALL, self::STATUS_INCOMPLETE, self::STATUS_COMPLETED], true)
            ? $status
            : self::STATUS_ALL;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLiveAttempts(CQuiz $quiz, Course $course, ?Session $session, int $minutes, string $status): array
    {
        $since = (new DateTime())->sub(new DateInterval(sprintf('PT%dM', $minutes)));

        $idQueryBuilder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT attempt.exeId AS attemptId')
            ->from(TrackEExercise::class, 'attempt')
            ->leftJoin('attempt.attempts', 'answer')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('(attempt.status = :incompleteStatus OR attempt.startDate >= :since OR attempt.exeDate >= :since OR answer.tms >= :since)')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('incompleteStatus', self::STATUS_INCOMPLETE, Types::STRING)
            ->setParameter('since', $since, Types::DATETIME_MUTABLE)
            ->orderBy('attempt.status', 'DESC')
            ->addOrderBy('attempt.exeDate', 'DESC')
            ->addOrderBy('attempt.exeId', 'DESC')
            ->setMaxResults(200)
        ;

        if (null !== $session) {
            $idQueryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $idQueryBuilder->andWhere('attempt.session IS NULL');
        }

        if (self::STATUS_INCOMPLETE === $status) {
            $idQueryBuilder
                ->andWhere('attempt.status = :status')
                ->setParameter('status', self::STATUS_INCOMPLETE, Types::STRING)
            ;
        } elseif (self::STATUS_COMPLETED === $status) {
            $idQueryBuilder
                ->andWhere('attempt.status <> :statusIncomplete')
                ->setParameter('statusIncomplete', self::STATUS_INCOMPLETE, Types::STRING)
            ;
        }

        $attemptIds = [];
        foreach ($idQueryBuilder->getQuery()->getArrayResult() as $row) {
            $attemptId = (int) ($row['attemptId'] ?? 0);
            if (0 < $attemptId) {
                $attemptIds[] = $attemptId;
            }
        }

        if ([] === $attemptIds) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt', 'user', 'answer')
            ->from(TrackEExercise::class, 'attempt')
            ->innerJoin('attempt.user', 'user')
            ->leftJoin('attempt.attempts', 'answer')
            ->andWhere('attempt.exeId IN (:attemptIds)')
            ->setParameter('attemptIds', $attemptIds)
            ->orderBy('attempt.status', 'DESC')
            ->addOrderBy('attempt.exeDate', 'DESC')
            ->addOrderBy('attempt.exeId', 'DESC')
        ;

        $attempts = [];
        foreach ($queryBuilder->getQuery()->getResult() as $attempt) {
            if (!$attempt instanceof TrackEExercise) {
                continue;
            }

            $attempts[] = $this->normalizeAttempt($attempt);
        }

        return $attempts;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAttempt(TrackEExercise $attempt): array
    {
        $user = $attempt->getUser();
        $answeredQuestions = $this->countAnsweredQuestions($attempt);
        $lastActivity = $this->getLastActivity($attempt);
        $status = self::STATUS_INCOMPLETE === (string) $attempt->getStatus()
            ? self::STATUS_INCOMPLETE
            : self::STATUS_COMPLETED;
        $statusLabel = self::STATUS_INCOMPLETE === $status ? 'Ongoing' : 'Completed';
        $score = $attempt->getScore();
        $maxScore = $attempt->getMaxScore();
        $percentage = 0.0 < $maxScore ? round(($score * 100) / $maxScore, 2) : 0.0;

        return [
            'id' => (int) $attempt->getExeId(),
            'attemptId' => (int) $attempt->getExeId(),
            'userId' => (int) $user->getId(),
            'username' => $user->getUsername(),
            'firstName' => (string) $user->getFirstname(),
            'lastName' => (string) $user->getLastname(),
            'fullName' => $user->getFullName(),
            'startedAt' => $this->formatDate($attempt->getStartDate()),
            'completedAt' => self::STATUS_INCOMPLETE === $status ? null : $this->formatDate($attempt->getExeDate()),
            'lastActivityAt' => $this->formatDate($lastActivity),
            'duration' => $attempt->getExeDuration(),
            'answeredQuestions' => $answeredQuestions,
            'score' => $score,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'ip' => $attempt->getUserIp(),
            'status' => $status,
            'statusLabel' => $statusLabel,
        ];
    }

    private function countAnsweredQuestions(TrackEExercise $attempt): int
    {
        $questionIds = [];
        foreach ($attempt->getAttempts() as $answer) {
            if (!$answer instanceof TrackEAttempt) {
                continue;
            }

            $questionId = (int) $answer->getQuestionId();
            if (0 < $questionId) {
                $questionIds[$questionId] = true;
            }
        }

        return \count($questionIds);
    }

    private function getLastActivity(TrackEExercise $attempt): DateTimeInterface
    {
        $lastActivity = $attempt->getStartDate();
        foreach ($attempt->getAttempts() as $answer) {
            if (!$answer instanceof TrackEAttempt) {
                continue;
            }

            if ($answer->getTms() > $lastActivity) {
                $lastActivity = $answer->getTms();
            }
        }

        if ($attempt->getExeDate() > $lastActivity) {
            $lastActivity = $attempt->getExeDate();
        }

        return $lastActivity;
    }

    /**
     * @param array<int, array<string, mixed>> $attempts
     *
     * @return array<string, int|float|string>
     */
    private function buildSummary(array $attempts, int $minutes): array
    {
        $ongoing = 0;
        $completed = 0;
        $answered = 0;
        foreach ($attempts as $attempt) {
            if (self::STATUS_INCOMPLETE === ($attempt['status'] ?? '')) {
                ++$ongoing;
            } else {
                ++$completed;
            }
            $answered += (int) ($attempt['answeredQuestions'] ?? 0);
        }

        return [
            'totalAttempts' => \count($attempts),
            'ongoingAttempts' => $ongoing,
            'completedAttempts' => $completed,
            'answeredQuestions' => $answered,
            'minutes' => $minutes,
        ];
    }

    private function formatDate(DateTimeInterface $date): string
    {
        return $date->format(DateTimeInterface::ATOM);
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
