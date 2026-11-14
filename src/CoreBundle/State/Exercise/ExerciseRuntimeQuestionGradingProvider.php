<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeQuestionGrading;
use Chamilo\CoreBundle\Entity\AttemptFile;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEAttemptQualify;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * Read side of the "grade by question" screen: the answers of every learner to one manually graded
 * question of an exercise, limited to the completed attempts of the current course and session.
 *
 * An answer counts as scored once a teacher saved a score for it, from this screen or from the full
 * review (both leave a track_e_attempt_qualify row with an author).
 *
 * @implements ProviderInterface<ExerciseRuntimeQuestionGrading>
 */
final readonly class ExerciseRuntimeQuestionGradingProvider implements ProviderInterface
{
    public const MANUAL_QUESTION_TYPES = [
        self::FREE_ANSWER,
        self::ORAL_EXPRESSION,
        self::ANNOTATION,
        self::UPLOAD_ANSWER,
        self::ANSWER_IN_OFFICE_DOC,
    ];

    private const FREE_ANSWER = 5;
    private const ORAL_EXPRESSION = 13;
    private const ANNOTATION = 20;
    private const UPLOAD_ANSWER = 23;
    private const ANSWER_IN_OFFICE_DOC = 30;
    private const STATUS_COMPLETED = 'completed';
    private const SORT_DATE = 'date';
    private const SORT_LASTNAME = 'lastname';
    private const DEFAULT_ITEMS_PER_PAGE = 20;
    private const MAX_ITEMS_PER_PAGE = 50;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private GradebookLinkManager $gradebookLinkManager,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeQuestionGrading
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $exerciseId = (int) ($uriVariables['exerciseId'] ?? 0);
        if ($exerciseId <= 0) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        // Same guard as the learner attempts report of the exercise.
        if (!$this->isAllowedToEditHelper->check(coach: true)) {
            throw new AccessDeniedHttpException('You are not allowed to grade this exercise.');
        }

        $quiz = $this->quizRepository->find($exerciseId);
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        if (null === $this->quizRepository->findInCourseContext($exerciseId, $course, $session)) {
            throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
        }

        $questions = $this->getManualQuestions($quiz);
        $unscoredCounts = $this->countUnscoredAnswers($quiz, $course, $session, array_keys($questions));

        $response = new ExerciseRuntimeQuestionGrading();
        $response->exerciseId = $exerciseId;
        $response->title = (string) $quiz->getTitle();
        $response->lockedByGradebook = $this->gradebookLinkManager->isResourceLocked(
            $course,
            $session,
            GradebookLinkResourceResolver::LINK_EXERCISE,
            (int) $quiz->getIid(),
        );
        $response->sort = self::SORT_LASTNAME === $request->query->get('sort') ? self::SORT_LASTNAME : self::SORT_DATE;
        $response->onlyUnscored = $request->query->getBoolean('onlyUnscored');
        $response->itemsPerPage = max(1, min(self::MAX_ITEMS_PER_PAGE, $request->query->getInt('itemsPerPage', self::DEFAULT_ITEMS_PER_PAGE)));
        $response->page = max(1, $request->query->getInt('page', 1));

        foreach ($questions as $questionId => $question) {
            $response->questions[] = [
                'id' => $questionId,
                'title' => $this->toPlainText((string) $question->getQuestion()),
                'type' => (int) $question->getType(),
                'maxScore' => (float) $question->getPonderation(),
                'unscoredCount' => $unscoredCounts[$questionId] ?? 0,
            ];
        }

        if ([] === $questions) {
            return $response;
        }

        $questionId = $request->query->getInt('questionId');
        if (0 === $questionId) {
            $questionId = (int) array_key_first($questions);
        }

        if (!isset($questions[$questionId])) {
            throw new NotFoundHttpException('The requested question is not a manually graded question of this exercise.');
        }

        $response->questionId = $questionId;
        $response->totalItems = $this->countAnswers($quiz, $course, $session, $questionId, $response->onlyUnscored);
        $response->items = $this->getAnswers(
            $request,
            $quiz,
            $course,
            $session,
            $questions[$questionId],
            $response,
        );

        return $response;
    }

    /**
     * @return array<int, CQuizQuestion> keyed by question id, in the exercise order
     */
    private function getManualQuestions(CQuiz $quiz): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relQuestion', 'question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->andWhere('question.type IN (:types)')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('types', self::MANUAL_QUESTION_TYPES, ArrayParameterType::INTEGER)
            ->orderBy('relQuestion.questionOrder', 'ASC')
            ->addOrderBy('relQuestion.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $questions = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            $questions[(int) $question->getIid()] = $question;
        }

        return $questions;
    }

    /**
     * Completed attempts of this exercise in the current course and session that answered the question.
     */
    private function createAnswersQueryBuilder(CQuiz $quiz, Course $course, ?Session $session, int $questionId, bool $onlyUnscored): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(TrackEExercise::class, 'attempt')
            ->innerJoin('attempt.user', 'learner')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('attempt.status = :status')
            ->andWhere(
                'EXISTS (SELECT answered.id FROM '.TrackEAttempt::class.' answered'
                .' WHERE answered.trackExercise = attempt AND answered.questionId = :questionId)'
            )
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('status', self::STATUS_COMPLETED)
            ->setParameter('questionId', $questionId, Types::INTEGER)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        if ($onlyUnscored) {
            $queryBuilder->andWhere(
                'NOT EXISTS (SELECT scored.id FROM '.TrackEAttemptQualify::class.' scored'
                .' WHERE scored.trackExercise = attempt AND scored.questionId = :questionId AND scored.author > 0)'
            );
        }

        return $queryBuilder;
    }

    private function countAnswers(CQuiz $quiz, Course $course, ?Session $session, int $questionId, bool $onlyUnscored): int
    {
        return (int) $this->createAnswersQueryBuilder($quiz, $course, $session, $questionId, $onlyUnscored)
            ->select('COUNT(attempt.exeId)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param array<int, int> $questionIds
     *
     * @return array<int, int>
     */
    private function countUnscoredAnswers(CQuiz $quiz, Course $course, ?Session $session, array $questionIds): array
    {
        $counts = [];
        foreach ($questionIds as $questionId) {
            $counts[$questionId] = $this->countAnswers($quiz, $course, $session, $questionId, true);
        }

        return $counts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAnswers(
        Request $request,
        CQuiz $quiz,
        Course $course,
        ?Session $session,
        CQuizQuestion $question,
        ExerciseRuntimeQuestionGrading $response,
    ): array {
        $questionId = (int) $question->getIid();
        $queryBuilder = $this->createAnswersQueryBuilder($quiz, $course, $session, $questionId, $response->onlyUnscored)
            ->select('attempt')
            ->addSelect('learner')
            ->setFirstResult(($response->page - 1) * $response->itemsPerPage)
            ->setMaxResults($response->itemsPerPage)
        ;

        if (self::SORT_LASTNAME === $response->sort) {
            $queryBuilder
                ->orderBy('learner.lastname', 'ASC')
                ->addOrderBy('learner.firstname', 'ASC')
                ->addOrderBy('attempt.exeDate', 'ASC')
            ;
        } else {
            $queryBuilder->orderBy('attempt.exeDate', 'ASC');
        }
        $queryBuilder->addOrderBy('attempt.exeId', 'ASC');

        /** @var array<int, TrackEExercise> $attempts */
        $attempts = array_values(array_filter(
            $queryBuilder->getQuery()->getResult(),
            static fn (mixed $attempt): bool => $attempt instanceof TrackEExercise,
        ));
        if ([] === $attempts) {
            return [];
        }

        $attemptIds = array_map(static fn (TrackEExercise $attempt): int => (int) $attempt->getExeId(), $attempts);
        $rows = $this->getFirstAnswerRows($attemptIds, $questionId);
        $scoredAttemptIds = $this->getScoredAttemptIds($attemptIds, $questionId);
        $kind = $this->getAnswerKind((int) $question->getType());

        $items = [];
        foreach ($attempts as $attempt) {
            $attemptId = (int) $attempt->getExeId();
            $row = $rows[$attemptId] ?? null;
            $learner = $attempt->getUser();

            $items[] = [
                'attemptId' => $attemptId,
                'userId' => (int) $learner->getId(),
                'firstName' => (string) $learner->getFirstname(),
                'lastName' => (string) $learner->getLastname(),
                'submittedAt' => $attempt->getExeDate()->format(DateTimeInterface::ATOM),
                'marks' => $row instanceof TrackEAttempt ? (float) $row->getMarks() : 0.0,
                'scored' => isset($scoredAttemptIds[$attemptId]),
                'awaitingReview' => \in_array($questionId, $this->parseQuestionIds((string) $attempt->getQuestionsToCheck()), true),
                'answer' => [
                    'kind' => $kind,
                    'text' => self::FREE_ANSWER === (int) $question->getType() && $row instanceof TrackEAttempt
                        ? $this->toPlainText((string) $row->getAnswer())
                        : '',
                    'files' => $row instanceof TrackEAttempt ? $this->getAttemptFiles($request, $attempt, $row) : [],
                ],
            ];
        }

        return $items;
    }

    /**
     * @param array<int, int> $attemptIds
     *
     * @return array<int, TrackEAttempt> the first saved row of the question for each attempt
     */
    private function getFirstAnswerRows(array $attemptIds, int $questionId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) IN (:attemptIds)')
            ->andWhere('saved.questionId = :questionId')
            ->setParameter('attemptIds', $attemptIds, ArrayParameterType::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->orderBy('saved.position', 'ASC')
            ->addOrderBy('saved.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $firstRows = [];
        foreach ($rows as $row) {
            if (!$row instanceof TrackEAttempt) {
                continue;
            }

            $attemptId = (int) $row->getTrackEExercise()->getExeId();
            $firstRows[$attemptId] ??= $row;
        }

        return $firstRows;
    }

    /**
     * @param array<int, int> $attemptIds
     *
     * @return array<int, true>
     */
    private function getScoredAttemptIds(array $attemptIds, int $questionId): array
    {
        $ids = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT IDENTITY(scored.trackExercise) AS attemptId')
            ->from(TrackEAttemptQualify::class, 'scored')
            ->andWhere('IDENTITY(scored.trackExercise) IN (:attemptIds)')
            ->andWhere('scored.questionId = :questionId')
            ->andWhere('scored.author > 0')
            ->setParameter('attemptIds', $attemptIds, ArrayParameterType::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->getQuery()
            ->getSingleColumnResult()
        ;

        $scored = [];
        foreach ($ids as $id) {
            $scored[(int) $id] = true;
        }

        return $scored;
    }

    private function getAnswerKind(int $type): string
    {
        return match ($type) {
            self::FREE_ANSWER => 'free_answer',
            self::ORAL_EXPRESSION => 'oral_expression',
            self::ANNOTATION => 'annotation',
            self::UPLOAD_ANSWER => 'upload_answer',
            default => 'onlyoffice',
        };
    }

    /**
     * @return array<int, array{id: int, name: string, mimeType: string, url: string}>
     */
    private function getAttemptFiles(Request $request, TrackEExercise $attempt, TrackEAttempt $row): array
    {
        $files = [];
        foreach ($row->getAttemptFiles() as $attemptFile) {
            if (!$attemptFile instanceof AttemptFile) {
                continue;
            }

            $resourceNode = $attemptFile->getResourceNode();
            if (!$resourceNode instanceof ResourceNode || null === $resourceNode->getId()) {
                continue;
            }

            $resourceFile = $resourceNode->getResourceFiles()->first();
            $query = ['cid' => (int) $attempt->getCourse()->getId(), 'inline' => 1];
            if (null !== $attempt->getSession()) {
                $query['sid'] = (int) $attempt->getSession()->getId();
            }

            $gid = $request->query->getInt('gid');
            if ($gid > 0) {
                $query['gid'] = $gid;
            }

            $files[] = [
                'id' => (int) $resourceNode->getId(),
                'name' => $resourceFile instanceof ResourceFile ? (string) $resourceFile->getOriginalName() : (string) $resourceNode->getTitle(),
                'mimeType' => $resourceFile instanceof ResourceFile ? (string) $resourceFile->getMimeType() : '',
                'url' => \sprintf(
                    '/api/exercise/runtime/%d/attempt/%d/file/%d/download?%s',
                    (int) $attempt->getQuiz()?->getIid(),
                    (int) $attempt->getExeId(),
                    (int) $resourceNode->getId(),
                    http_build_query($query),
                ),
            ];
        }

        return $files;
    }

    /**
     * The screen prints answers and question titles as text, so markup typed through the editor is
     * reduced to its text, keeping the line breaks.
     */
    private function toPlainText(string $html): string
    {
        $text = (string) preg_replace('/<\s*br\s*\/?>|<\/\s*(p|div|li|h[1-6])\s*>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /**
     * @return array<int, int>
     */
    private function parseQuestionIds(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn (string $id): int => (int) trim($id), preg_split('/[,;]+/', $value) ?: [])));
    }
}
