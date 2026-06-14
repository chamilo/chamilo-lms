<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeCorrection;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEAttemptQualify;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Corrects manual Vue runtime answers using the migrated tracking flow.
 *
 * @implements ProcessorInterface<ExerciseRuntimeCorrection, ExerciseRuntimeCorrection>
 */
final readonly class ExerciseRuntimeCorrectionProcessor implements ProcessorInterface
{
    private const STATUS_COMPLETED = 'completed';
    private const FREE_ANSWER = 5;
    private const ORAL_EXPRESSION = 13;
    private const UPLOAD_ANSWER = 23;
    private const ANNOTATION = 20;
    private const LINK_TYPE_EXERCISE = 1;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeCorrection
    {
        if (!$data instanceof ExerciseRuntimeCorrection) {
            throw new BadRequestHttpException('Invalid exercise runtime correction payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canCorrectAttempts()) {
            throw new AccessDeniedHttpException('You are not allowed to correct this exercise attempt.');
        }

        if (!$this->security->getUser() instanceof User) {
            throw new AccessDeniedHttpException('A valid authenticated user is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        $attemptId = isset($uriVariables['attemptId']) ? (int) $uriVariables['attemptId'] : (int) ($data->attemptId ?? 0);
        $questionId = (int) ($data->questionId ?? 0);

        if (0 >= $exerciseId || 0 >= $attemptId || 0 >= $questionId) {
            throw new BadRequestHttpException('A valid exercise, attempt and question are required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        if ($this->isGradebookLocked((int) $quiz->getIid(), $course)) {
            throw new BadRequestHttpException('This exercise is locked by gradebook.');
        }

        $attempt = $this->getCompletedAttempt($attemptId, $quiz, $course, $session);
        $question = $this->getQuestionFromExercise($questionId, $quiz);
        if (!$question instanceof CQuizQuestion) {
            throw new NotFoundHttpException('The requested question was not found in this exercise.');
        }

        if (!$this->isManualCorrectionQuestion($question)) {
            throw new BadRequestHttpException('This question type is not supported by the Vue manual correction flow yet.');
        }

        $maxScore = (float) $question->getPonderation();
        $marks = (float) $data->marks;
        if (0.0 > $marks || $marks > $maxScore) {
            throw new BadRequestHttpException('The correction score must be between zero and the question maximum score.');
        }

        $rows = $this->getAttemptRows($attemptId, $questionId);
        if ([] === $rows) {
            $rows[] = $this->createEmptyAttemptRow($attempt, $questionId);
        }

        foreach ($rows as $row) {
            $row
                ->setMarks($marks)
                ->setTeacherComment((string) $data->teacherComment)
                ->setTms(new DateTime())
            ;
        }

        $this->recordCorrectionHistory($attempt, $questionId, $marks, (string) $data->teacherComment, (int) ($session?->getId() ?? 0));
        $this->removeQuestionToCheck($attempt, $questionId);
        $this->recalculateAttemptScore($attempt, $quiz);
        $this->entityManager->flush();

        $response = new ExerciseRuntimeCorrection();
        $response->exerciseId = $exerciseId;
        $response->attemptId = $attemptId;
        $response->questionId = $questionId;
        $response->marks = $marks;
        $response->teacherComment = (string) $data->teacherComment;
        $response->success = true;
        $response->message = 'Correction saved';
        $response->score = (float) $attempt->getScore();
        $response->maxScore = (float) $attempt->getMaxScore();
        $response->questionsToCheck = $this->parseQuestionIds((string) $attempt->getQuestionsToCheck());

        return $response;
    }

    private function isManualCorrectionQuestion(CQuizQuestion $question): bool
    {
        return \in_array((int) $question->getType(), [self::FREE_ANSWER, self::ORAL_EXPRESSION, self::UPLOAD_ANSWER, self::ANNOTATION], true);
    }

    private function canCorrectAttempts(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
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

        return $quiz;
    }

    private function getCompletedAttempt(int $attemptId, CQuiz $quiz, Course $course, ?Session $session): TrackEExercise
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('attempt.exeId = :attemptId')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('attempt.status = :status')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('status', self::STATUS_COMPLETED)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $attempt = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$attempt instanceof TrackEExercise) {
            throw new NotFoundHttpException('The requested completed attempt was not found.');
        }

        return $attempt;
    }

    private function getQuestionFromExercise(int $questionId, CQuiz $quiz): ?CQuizQuestion
    {
        $relQuestion = $this->entityManager->createQueryBuilder()
            ->select('relQuestion')
            ->addSelect('question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->andWhere('IDENTITY(relQuestion.question) = :questionId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$relQuestion instanceof CQuizRelQuestion) {
            return null;
        }

        return $relQuestion->getQuestion();
    }

    /**
     * @return array<int, TrackEAttempt>
     */
    private function getAttemptRows(int $attemptId, int $questionId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->andWhere('saved.questionId = :questionId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->orderBy('saved.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_values(array_filter($rows, static fn (mixed $row): bool => $row instanceof TrackEAttempt));
    }

    private function createEmptyAttemptRow(TrackEExercise $attempt, int $questionId): TrackEAttempt
    {
        $row = (new TrackEAttempt())
            ->setTrackEExercise($attempt)
            ->setUser($attempt->getUser())
            ->setQuestionId($questionId)
            ->setAnswer('')
            ->setTeacherComment('')
            ->setMarks(0.0)
            ->setPosition(0)
            ->setTms(new DateTime())
            ->setSecondsSpent(0)
        ;

        $this->entityManager->persist($row);

        return $row;
    }


    private function isGradebookLocked(int $exerciseId, Course $course): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        if (!$this->isSettingEnabled('gradebook.gradebook_locking_enabled')) {
            return false;
        }

        $lockedLink = $this->entityManager->createQueryBuilder()
            ->select('link.id')
            ->from(GradebookLink::class, 'link')
            ->andWhere('link.locked = :locked')
            ->andWhere('link.refId = :exerciseId')
            ->andWhere('link.type = :linkType')
            ->andWhere('IDENTITY(link.course) = :courseId')
            ->setParameter('locked', 1, Types::INTEGER)
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('linkType', self::LINK_TYPE_EXERCISE, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null !== $lockedLink;
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }

    private function recordCorrectionHistory(TrackEExercise $attempt, int $questionId, float $marks, string $teacherComment, int $sessionId): void
    {
        $user = $this->security->getUser();
        $authorId = $user instanceof User ? (int) $user->getId() : 0;

        $recording = (new TrackEAttemptQualify())
            ->setTrackExercise($attempt)
            ->setQuestionId($questionId)
            ->setAuthor($authorId)
            ->setTeacherComment($teacherComment)
            ->setMarks($marks)
            ->setSessionId($sessionId)
        ;

        $this->entityManager->persist($recording);
    }

    private function removeQuestionToCheck(TrackEExercise $attempt, int $questionId): void
    {
        $pendingQuestions = array_values(array_filter(
            $this->parseQuestionIds((string) $attempt->getQuestionsToCheck()),
            static fn (int $pendingQuestionId): bool => $pendingQuestionId !== $questionId,
        ));

        $attempt->setQuestionsToCheck(implode(',', $pendingQuestions));
    }

    private function recalculateAttemptScore(TrackEExercise $attempt, CQuiz $quiz): void
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->setParameter('attemptId', (int) $attempt->getExeId(), Types::INTEGER)
            ->orderBy('saved.questionId', 'ASC')
            ->addOrderBy('saved.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $seenQuestions = [];
        $score = 0.0;
        foreach ($rows as $row) {
            if (!$row instanceof TrackEAttempt) {
                continue;
            }

            $questionId = (int) $row->getQuestionId();
            if (isset($seenQuestions[$questionId])) {
                continue;
            }

            $marks = (float) $row->getMarks();
            if (0 === (int) $quiz->getPropagateNeg() && 0 > $marks) {
                $seenQuestions[$questionId] = true;
                continue;
            }

            $score += $marks;
            $seenQuestions[$questionId] = true;
        }

        $attempt->setScore($score);
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
