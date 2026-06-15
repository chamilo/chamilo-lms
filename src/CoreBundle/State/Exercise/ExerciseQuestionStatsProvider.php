<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionStats;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Read-only question statistics for the migrated exercise report.
 *
 * This provider exposes teacher-side stats through modern API/PDF endpoints:
 * attempts per question, lowest/average/highest score and wrong/total counts.
 *
 * @implements ProviderInterface<ExerciseQuestionStats>
 */
final readonly class ExerciseQuestionStatsProvider implements ProviderInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_COMPLETED = '';

    private const TYPE_NAMES = [
        1 => 'Unique answer',
        2 => 'Multiple answer',
        3 => 'Fill in blanks',
        4 => 'Matching',
        5 => 'Free answer',
        6 => 'Hotspot',
        9 => 'Multiple answer combination',
        10 => 'Unique answer with unknown',
        11 => 'Multiple answer true/false',
        12 => 'Multiple answer combination true/false',
        13 => 'Oral expression',
        14 => 'Global multiple answer',
        15 => 'Media question',
        16 => 'Calculated answer',
        17 => 'Unique answer with images',
        18 => 'Sequence ordering',
        19 => 'Matching draggable',
        20 => 'Annotation',
        21 => 'Reading comprehension',
        22 => 'Multiple answer true/false with degree of certainty',
        23 => 'Upload answer',
        24 => 'Matching combination',
        25 => 'Matching draggable combination',
        26 => 'Hotspot combination',
        27 => 'Fill in blanks combination',
        28 => 'Multiple answer dropdown combination',
        29 => 'Multiple answer dropdown',
        31 => 'Page break',
    ];

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseQuestionStats
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to view these exercise statistics.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $questions = $this->getExerciseQuestions($quiz);
        $attemptMarks = $this->getQuestionAttemptMarks($quiz, $course, $session, array_keys($questions));
        $rows = $this->buildRows($questions, $attemptMarks);

        $response = new ExerciseQuestionStats();
        $response->exerciseId = $exerciseId;
        $response->title = $quiz->getTitle();
        $response->description = (string) $quiz->getDescription();
        $response->questions = $rows;
        $response->summary = $this->buildSummary($rows);
        $response->actionUrls = $this->getActionUrls($quiz, $request);

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
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
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
     * @return array<int, array<string, mixed>> keyed by question id
     */
    private function getExerciseQuestions(CQuiz $quiz): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relation', 'question')
            ->from(CQuizRelQuestion::class, 'relation')
            ->innerJoin('relation.question', 'question')
            ->andWhere('IDENTITY(relation.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->orderBy('relation.questionOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $questions = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            $questionId = (int) $question->getIid();
            if (isset($questions[$questionId])) {
                continue;
            }

            $type = (int) $question->getType();
            $questions[$questionId] = [
                'id' => $questionId,
                'title' => $question->getQuestion(),
                'type' => $type,
                'typeLabel' => self::TYPE_NAMES[$type] ?? sprintf('Type %d', $type),
                'maxScore' => (float) $question->getPonderation(),
                'position' => (int) $relation->getQuestionOrder(),
            ];
        }

        return $questions;
    }

    /**
     * @param array<int, int> $questionIds
     *
     * @return array<int, array<int, float>> question id => attempt id => marks
     */
    private function getQuestionAttemptMarks(CQuiz $quiz, Course $course, ?Session $session, array $questionIds): array
    {
        if ([] === $questionIds) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt.questionId AS questionId')
            ->addSelect('IDENTITY(attempt.trackExercise) AS attemptId')
            ->addSelect('attempt.marks AS marks')
            ->from(TrackEAttempt::class, 'attempt')
            ->innerJoin('attempt.trackExercise', 'trackedExercise')
            ->andWhere('IDENTITY(trackedExercise.quiz) = :exerciseId')
            ->andWhere('IDENTITY(trackedExercise.course) = :courseId')
            ->andWhere('trackedExercise.status = :completedStatus')
            ->andWhere('attempt.questionId IN (:questionIds)')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('completedStatus', self::STATUS_COMPLETED, Types::STRING)
            ->setParameter('questionIds', array_values($questionIds))
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(trackedExercise.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('trackedExercise.session IS NULL');
        }

        $marks = [];
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $questionId = (int) ($row['questionId'] ?? 0);
            $attemptId = (int) ($row['attemptId'] ?? 0);
            if (0 >= $questionId || 0 >= $attemptId) {
                continue;
            }

            $marks[$questionId][$attemptId] = ($marks[$questionId][$attemptId] ?? 0.0) + (float) ($row['marks'] ?? 0);
        }

        return $marks;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<int, array<int, float>> $attemptMarks
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRows(array $questions, array $attemptMarks): array
    {
        $rows = [];
        foreach ($questions as $questionId => $question) {
            $marks = array_values($attemptMarks[$questionId] ?? []);
            $answeredAttempts = \count($marks);
            $maxScore = (float) ($question['maxScore'] ?? 0.0);
            $lowestScore = $answeredAttempts > 0 ? min($marks) : 0.0;
            $highestScore = $answeredAttempts > 0 ? max($marks) : 0.0;
            $averageScore = $answeredAttempts > 0 ? array_sum($marks) / $answeredAttempts : 0.0;
            $wrongAttempts = 0;

            foreach ($marks as $mark) {
                if ($mark < $maxScore) {
                    $wrongAttempts++;
                }
            }

            $wrongPercentage = $answeredAttempts > 0 ? round(($wrongAttempts * 100) / $answeredAttempts, 2) : 0.0;

            $rows[] = [
                'id' => $questionId,
                'questionId' => $questionId,
                'title' => (string) ($question['title'] ?? ''),
                'type' => (int) ($question['type'] ?? 0),
                'typeLabel' => (string) ($question['typeLabel'] ?? ''),
                'position' => (int) ($question['position'] ?? 0),
                'answeredAttempts' => $answeredAttempts,
                'wrongAttempts' => $wrongAttempts,
                'wrongPercentage' => $wrongPercentage,
                'lowestScore' => round($lowestScore, 2),
                'averageScore' => round($averageScore, 2),
                'highestScore' => round($highestScore, 2),
                'maxScore' => round($maxScore, 2),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, int|float>
     */
    private function buildSummary(array $rows): array
    {
        $totalAnswered = 0;
        $totalWrong = 0;
        foreach ($rows as $row) {
            $totalAnswered += (int) ($row['answeredAttempts'] ?? 0);
            $totalWrong += (int) ($row['wrongAttempts'] ?? 0);
        }

        return [
            'totalQuestions' => \count($rows),
            'totalAnswered' => $totalAnswered,
            'totalWrong' => $totalWrong,
            'wrongPercentage' => $totalAnswered > 0 ? round(($totalWrong * 100) / $totalAnswered, 2) : 0.0,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getActionUrls(CQuiz $quiz, Request $request): array
    {
        $exerciseId = (int) $quiz->getIid();
        $params = $this->getBaseParams($exerciseId, $request);

        return [
            'questionStatsPdf' => '/api/exercise/runtime/'.$exerciseId.'/question-stats.pdf?'.http_build_query($params),
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
