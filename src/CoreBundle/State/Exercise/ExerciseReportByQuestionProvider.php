<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseReportByQuestion;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CoreBundle\Service\Exercise\ExerciseSpecialQuestionReportCounterService;
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
 * Read-only report by question.
 *
 * The migrated report exposes configured answer distributions for answer-id based
 * question types and uses modern PDF export endpoints. Complex answer counters
 * remain explicit in the response instead of being proxied through old PHP pages.
 *
 * @implements ProviderInterface<ExerciseReportByQuestion>
 */
final readonly class ExerciseReportByQuestionProvider implements ProviderInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_COMPLETED = '';

    /**
     * Question types that require dedicated counters/parsers instead of
     * a simple track_e_attempt.answer = c_quiz_answer.iid count.
     */
    private const SPECIAL_COUNTING_TYPES = [
        3,  // Fill in blanks
        4,  // Matching
        6,  // Hotspot
        19, // Matching draggable
        24, // Matching combination
        25, // Matching draggable combination
        26, // Hotspot combination
        27, // Fill in blanks combination
        28, // Multiple answer dropdown combination
    ];

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
        private ExerciseSpecialQuestionReportCounterService $specialQuestionReportCounterService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseReportByQuestion
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to view this report.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $questions = $this->getExerciseQuestions($quiz);
        $answerCounts = $this->getAnswerCounts($quiz, $course, $session, $this->getAnswerIds($questions));
        $specialAnswerDistributions = $this->specialQuestionReportCounterService->buildDistributions($quiz, $course, $session, $questions);
        $rows = $this->buildQuestionRows($questions, $answerCounts, $specialAnswerDistributions);

        $response = new ExerciseReportByQuestion();
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
            ->select('relation', 'question', 'answer')
            ->from(CQuizRelQuestion::class, 'relation')
            ->innerJoin('relation.question', 'question')
            ->leftJoin('question.answers', 'answer')
            ->andWhere('IDENTITY(relation.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->orderBy('relation.questionOrder', 'ASC')
            ->addOrderBy('answer.position', 'ASC')
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
            if (31 === $type) {
                continue;
            }

            $answers = [];
            foreach ($question->getAnswers() as $answer) {
                if (!$answer instanceof CQuizAnswer) {
                    continue;
                }

                $answerId = (int) $answer->getIid();
                $answers[$answerId] = [
                    'id' => $answerId,
                    'answerId' => $answerId,
                    'answer' => (string) $answer->getAnswer(),
                    'correct' => 1 === (int) $answer->getCorrect(),
                    'correctValue' => (int) $answer->getCorrect(),
                    'score' => round((float) $answer->getPonderation(), 2),
                    'position' => (int) $answer->getPosition(),
                ];
            }

            uasort($answers, static fn (array $left, array $right): int => ($left['position'] <=> $right['position']) ?: ($left['id'] <=> $right['id']));

            $questions[$questionId] = [
                'id' => $questionId,
                'questionId' => $questionId,
                'title' => (string) $question->getQuestion(),
                'type' => $type,
                'typeLabel' => self::TYPE_NAMES[$type] ?? sprintf('Type %d', $type),
                'maxScore' => round((float) $question->getPonderation(), 2),
                'position' => (int) $relation->getQuestionOrder(),
                'extra' => (string) $question->getExtra(),
                'usesSpecialCounting' => \in_array($type, self::SPECIAL_COUNTING_TYPES, true),
                'answers' => array_values($answers),
            ];
        }

        return $questions;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     *
     * @return array<int, string>
     */
    private function getAnswerIds(array $questions): array
    {
        $answerIds = [];
        foreach ($questions as $question) {
            if (!empty($question['usesSpecialCounting'])) {
                continue;
            }

            foreach (($question['answers'] ?? []) as $answer) {
                $answerId = (int) ($answer['answerId'] ?? 0);
                if (0 < $answerId) {
                    $answerIds[$answerId] = (string) $answerId;
                }
            }
        }

        return $answerIds;
    }

    /**
     * @param array<int, string> $answerIds
     *
     * @return array<int, array<int, int>> question id => answer id => count
     */
    private function getAnswerCounts(CQuiz $quiz, Course $course, ?Session $session, array $answerIds): array
    {
        if ([] === $answerIds) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt.questionId AS questionId')
            ->addSelect('attempt.answer AS answerValue')
            ->addSelect('COUNT(attempt.id) AS selectedCount')
            ->from(TrackEAttempt::class, 'attempt')
            ->innerJoin('attempt.trackExercise', 'trackedExercise')
            ->andWhere('IDENTITY(trackedExercise.quiz) = :exerciseId')
            ->andWhere('IDENTITY(trackedExercise.course) = :courseId')
            ->andWhere('trackedExercise.status = :completedStatus')
            ->andWhere('attempt.answer IN (:answerIds)')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('completedStatus', self::STATUS_COMPLETED, Types::STRING)
            ->setParameter('answerIds', array_values($answerIds))
            ->groupBy('attempt.questionId')
            ->addGroupBy('attempt.answer')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(trackedExercise.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('trackedExercise.session IS NULL');
        }

        $counts = [];
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $questionId = (int) ($row['questionId'] ?? 0);
            $answerId = (int) ($row['answerValue'] ?? 0);
            if (0 >= $questionId || 0 >= $answerId) {
                continue;
            }

            $counts[$questionId][$answerId] = (int) ($row['selectedCount'] ?? 0);
        }

        return $counts;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @param array<int, array<int, int>> $answerCounts
     * @param array<int, array{answers: array<int, array<string, mixed>>, totalSelections: int, countingAvailable: bool}> $specialAnswerDistributions
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildQuestionRows(array $questions, array $answerCounts, array $specialAnswerDistributions): array
    {
        $rows = [];
        foreach ($questions as $questionId => $question) {
            $answers = [];
            $totalSelections = 0;
            $usesSpecialCounting = !empty($question['usesSpecialCounting']);

            $countingAvailable = false;
            $specialDistribution = $specialAnswerDistributions[$questionId] ?? null;
            if ($usesSpecialCounting && \is_array($specialDistribution)) {
                $answers = $specialDistribution['answers'];
                $totalSelections = (int) $specialDistribution['totalSelections'];
                $countingAvailable = (bool) $specialDistribution['countingAvailable'];
            } else {
                foreach (($question['answers'] ?? []) as $answer) {
                    $answerId = (int) ($answer['answerId'] ?? 0);
                    $selectedCount = $usesSpecialCounting ? null : (int) ($answerCounts[$questionId][$answerId] ?? 0);
                    if (null !== $selectedCount) {
                        $totalSelections += $selectedCount;
                    }

                    $answers[] = [
                        'id' => $answerId,
                        'answerId' => $answerId,
                        'answer' => (string) ($answer['answer'] ?? ''),
                        'correct' => (bool) ($answer['correct'] ?? false),
                        'correctValue' => (int) ($answer['correctValue'] ?? 0),
                        'score' => (float) ($answer['score'] ?? 0.0),
                        'position' => (int) ($answer['position'] ?? 0),
                        'selectedCount' => $selectedCount,
                        'selectedPercentage' => 0.0,
                    ];
                }

                if (0 < $totalSelections) {
                    foreach ($answers as &$answerRow) {
                        if (null !== $answerRow['selectedCount']) {
                            $answerRow['selectedPercentage'] = round(((int) $answerRow['selectedCount'] * 100) / $totalSelections, 2);
                        }
                    }
                    unset($answerRow);
                }
            }

            $rows[] = [
                'id' => $questionId,
                'questionId' => $questionId,
                'title' => (string) ($question['title'] ?? ''),
                'type' => (int) ($question['type'] ?? 0),
                'typeLabel' => (string) ($question['typeLabel'] ?? ''),
                'position' => (int) ($question['position'] ?? 0),
                'maxScore' => (float) ($question['maxScore'] ?? 0.0),
                'answers' => $answers,
                'totalSelections' => $totalSelections,
                'usesSpecialCounting' => $usesSpecialCounting,
                'countingAvailable' => $countingAvailable,
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
        $totalAnswers = 0;
        $totalSelections = 0;
        $specialCountingQuestions = 0;

        foreach ($rows as $row) {
            $totalAnswers += \count($row['answers'] ?? []);
            $totalSelections += (int) ($row['totalSelections'] ?? 0);
            if (!empty($row['usesSpecialCounting']) && empty($row['countingAvailable'])) {
                $specialCountingQuestions++;
            }
        }

        return [
            'totalQuestions' => \count($rows),
            'totalAnswers' => $totalAnswers,
            'totalSelections' => $totalSelections,
            'specialCountingQuestions' => $specialCountingQuestions,
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
            'reportByQuestionPdf' => '/api/exercise/runtime/'.$exerciseId.'/report-by-question.pdf?'.http_build_query($params),
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
