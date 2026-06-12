<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeFinish;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Finishes Vue runtime attempts using native Symfony/Doctrine scoring rules mirrored from the verified legacy rules.
 *
 * @implements ProcessorInterface<ExerciseRuntimeFinish, ExerciseRuntimeFinish>
 */
final readonly class ExerciseRuntimeFinishProcessor implements ProcessorInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const STATUS_COMPLETED = 'completed';
    private const UNIQUE_ANSWER = 1;
    private const MULTIPLE_ANSWER = 2;
    private const FILL_IN_BLANKS = 3;
    private const MATCHING = 4;
    private const FREE_ANSWER = 5;
    private const UPLOAD_ANSWER = 23;
    private const MULTIPLE_ANSWER_COMBINATION = 9;
    private const UNIQUE_ANSWER_NO_OPTION = 10;
    private const MULTIPLE_ANSWER_TRUE_FALSE = 11;
    private const MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE = 12;
    private const GLOBAL_MULTIPLE_ANSWER = 14;
    private const UNIQUE_ANSWER_IMAGE = 17;
    private const MATCHING_DRAGGABLE = 19;
    private const FILL_IN_BLANKS_COMBINATION = 27;
    private const MULTIPLE_ANSWER_DROPDOWN_COMBINATION = 28;
    private const MULTIPLE_ANSWER_DROPDOWN = 29;
    private const MATCHING_COMBINATION = 24;
    private const MATCHING_DRAGGABLE_COMBINATION = 25;

    /**
     * @var array<int, string>
     */
    private const SUPPORTED_TYPE_NAMES = [
        self::UNIQUE_ANSWER => 'Unique answer',
        self::UNIQUE_ANSWER_NO_OPTION => 'Unique answer no option',
        self::UNIQUE_ANSWER_IMAGE => 'Unique answer with images',
        self::MULTIPLE_ANSWER => 'Multiple answer',
        self::GLOBAL_MULTIPLE_ANSWER => 'Global multiple answer',
        self::MULTIPLE_ANSWER_COMBINATION => 'Multiple answer combination',
        self::MULTIPLE_ANSWER_TRUE_FALSE => 'Multiple answer true/false',
        self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => 'Multiple answer combination true/false',
        self::FILL_IN_BLANKS => 'Fill in blanks',
        self::FILL_IN_BLANKS_COMBINATION => 'Fill in blanks combination',
        self::MATCHING => 'Matching',
        self::MATCHING_DRAGGABLE => 'Matching draggable',
        self::MATCHING_COMBINATION => 'Matching combination',
        self::MATCHING_DRAGGABLE_COMBINATION => 'Matching draggable combination',
        self::MULTIPLE_ANSWER_DROPDOWN => 'Multiple answer dropdown',
        self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION => 'Multiple answer dropdown combination',
        self::FREE_ANSWER => 'Free answer',
        self::UPLOAD_ANSWER => 'Upload answer',
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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeFinish
    {
        if (!$data instanceof ExerciseRuntimeFinish) {
            throw new BadRequestHttpException('Invalid exercise runtime finish payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canFinishAttempt()) {
            throw new AccessDeniedHttpException('You are not allowed to finish this exercise attempt.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid authenticated user is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        $attemptId = isset($uriVariables['attemptId']) ? (int) $uriVariables['attemptId'] : (int) ($data->attemptId ?? 0);

        if (0 >= $exerciseId || 0 >= $attemptId) {
            throw new BadRequestHttpException('A valid exercise and attempt are required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $attempt = $this->getIncompleteAttempt($attemptId, $quiz, $course, $session, $user);
        $questionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        if ([] === $questionIds) {
            throw new BadRequestHttpException('The attempt does not contain a persisted question list.');
        }

        $questions = $this->getQuestions($quiz, $questionIds);
        $unsupportedTypes = $this->getUnsupportedQuestionTypes($questions);
        if ([] !== $unsupportedTypes) {
            throw new BadRequestHttpException('This attempt contains question types that are not supported by the Vue finish scorer yet: '.implode(', ', $unsupportedTypes).'.');
        }

        $totalScore = 0.0;
        $totalWeight = 0.0;
        $questionsToCheck = [];

        foreach ($questionIds as $questionId) {
            $question = $questions[$questionId] ?? null;
            if (!$question instanceof CQuizQuestion) {
                continue;
            }

            $rows = $this->getAttemptRows($attemptId, $questionId);
            $answers = $this->getQuestionAnswers($questionId);
            $options = $this->getQuestionOptions($questionId);
            $score = $this->scoreQuestion($quiz, $question, $answers, $options, $rows);
            $weight = $this->getQuestionWeight($question, $answers);

            if (0 === (int) $quiz->getPropagateNeg() && 0 > $score) {
                $score = 0.0;
            }

            $this->updateQuestionAttemptRows($question, $rows, $score);

            if ($this->requiresManualCorrection($question)) {
                $questionsToCheck[] = $questionId;
            }

            $totalScore += $score;
            $totalWeight += $weight;
        }

        if (0.0 >= $totalWeight) {
            $totalWeight = (float) $attempt->getMaxScore();
        }

        $finishedAt = $this->getFinishedAt($attempt);
        $duration = max(0, $finishedAt->getTimestamp() - $attempt->getStartDate()->getTimestamp());

        $attempt
            ->setScore($totalScore)
            ->setMaxScore($totalWeight)
            ->setStatus(self::STATUS_COMPLETED)
            ->setExeDate($finishedAt)
            ->setExeDuration($duration)
            ->setQuestionsToCheck(implode(',', $questionsToCheck))
        ;

        $this->entityManager->flush();

        $response = new ExerciseRuntimeFinish();
        $response->exerciseId = $exerciseId;
        $response->attemptId = $attemptId;
        $response->success = true;
        $response->message = 'Attempt finished';
        $response->status = self::STATUS_COMPLETED;
        $response->score = $totalScore;
        $response->maxScore = $totalWeight;
        $response->completedAt = $finishedAt->format(DateTimeInterface::ATOM);
        $response->resultUrl = '';

        return $response;
    }

    private function canFinishAttempt(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT');
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

        $visibility = \is_array($row) ? (int) ($row['linkVisibility'] ?? 0) : 0;
        $now = new DateTime();
        if (self::VISIBILITY_PUBLISHED !== $visibility) {
            throw new AccessDeniedHttpException('The requested exercise is not visible.');
        }

        if (null !== $quiz->getStartTime() && $quiz->getStartTime() > $now) {
            throw new AccessDeniedHttpException('The requested exercise is not available yet.');
        }

        if (null !== $quiz->getEndTime() && $quiz->getEndTime() < $now) {
            throw new AccessDeniedHttpException('The requested exercise is closed.');
        }

        return $quiz;
    }

    private function getIncompleteAttempt(int $attemptId, CQuiz $quiz, Course $course, ?Session $session, User $user): TrackEExercise
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('attempt.exeId = :attemptId')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->andWhere('IDENTITY(attempt.user) = :userId')
            ->andWhere('attempt.status = :status')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('status', self::STATUS_INCOMPLETE)
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
            throw new NotFoundHttpException('The requested incomplete attempt was not found.');
        }

        return $attempt;
    }

    /**
     * @return array<int, int>
     */
    private function parseQuestionIds(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn (string $id): int => (int) trim($id), explode(',', $value))));
    }

    /**
     * @param array<int, int> $questionIds
     *
     * @return array<int, CQuizQuestion>
     */
    private function getQuestions(CQuiz $quiz, array $questionIds): array
    {
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relQuestion')
            ->addSelect('question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->andWhere('IDENTITY(relQuestion.question) IN (:questionIds)')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('questionIds', $questionIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $questions = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof CQuizRelQuestion) {
                continue;
            }

            $question = $relation->getQuestion();
            if (null === $question->getIid()) {
                continue;
            }

            $questions[(int) $question->getIid()] = $question;
        }

        return $questions;
    }

    /**
     * @param array<int, CQuizQuestion> $questions
     *
     * @return array<int, string>
     */
    private function getUnsupportedQuestionTypes(array $questions): array
    {
        $unsupportedTypes = [];
        foreach ($questions as $question) {
            $type = (int) $question->getType();
            if (!$this->isSupportedQuestionType($type)) {
                $unsupportedTypes[$type] = self::SUPPORTED_TYPE_NAMES[$type] ?? (string) $type;
            }
        }

        return $unsupportedTypes;
    }

    private function isSupportedQuestionType(int $type): bool
    {
        return isset(self::SUPPORTED_TYPE_NAMES[$type]);
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

        $result = [];
        foreach ($rows as $row) {
            if ($row instanceof TrackEAttempt) {
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function getQuestionAnswers(int $questionId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->addOrderBy('answer.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $answers = [];
        foreach ($rows as $row) {
            if (!$row instanceof CQuizAnswer || null === $row->getIid()) {
                continue;
            }

            $answers[(int) $row->getIid()] = $row;
        }

        return $answers;
    }

    /**
     * @return array<int, CQuizQuestionOption>
     */
    private function getQuestionOptions(int $questionId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('questionOption')
            ->from(CQuizQuestionOption::class, 'questionOption')
            ->andWhere('IDENTITY(questionOption.question) = :questionId')
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $options = [];
        foreach ($rows as $row) {
            if (!$row instanceof CQuizQuestionOption || null === $row->getIid()) {
                continue;
            }

            $options[(int) $row->getIid()] = $row;
        }

        return $options;
    }

    /**
     * @param array<int, CQuizAnswer>         $answers
     * @param array<int, CQuizQuestionOption> $options
     * @param array<int, TrackEAttempt>       $rows
     */
    private function scoreQuestion(CQuiz $quiz, CQuizQuestion $question, array $answers, array $options, array $rows): float
    {
        return match ((int) $question->getType()) {
            self::UNIQUE_ANSWER,
            self::UNIQUE_ANSWER_NO_OPTION,
            self::UNIQUE_ANSWER_IMAGE => $this->scoreUniqueAnswer($answers, $rows),
            self::MULTIPLE_ANSWER,
            self::GLOBAL_MULTIPLE_ANSWER,
            self::MULTIPLE_ANSWER_DROPDOWN => $this->scoreMultipleAnswer($answers, $rows),
            self::MULTIPLE_ANSWER_COMBINATION,
            self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION => $this->scoreMultipleCombination($question, $answers, $rows),
            self::MULTIPLE_ANSWER_TRUE_FALSE => $this->scoreTrueFalseAnswer($question, $answers, $options, $rows),
            self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => $this->scoreTrueFalseCombination($question, $answers, $options, $rows),
            self::FILL_IN_BLANKS,
            self::FILL_IN_BLANKS_COMBINATION => $this->scoreFillBlanks($quiz, $question, $answers, $rows),
            self::MATCHING,
            self::MATCHING_DRAGGABLE => $this->scoreMatchingAnswer($answers, $rows),
            self::MATCHING_COMBINATION,
            self::MATCHING_DRAGGABLE_COMBINATION => $this->scoreMatchingCombination($question, $answers, $rows),
            self::FREE_ANSWER,
            self::UPLOAD_ANSWER => $this->scoreManualAnswer($rows),
            default => 0.0,
        };
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreUniqueAnswer(array $answers, array $rows): float
    {
        $selectedAnswerId = $this->getFirstSavedAnswerId($rows);
        if (0 >= $selectedAnswerId || !isset($answers[$selectedAnswerId])) {
            return 0.0;
        }

        return $answers[$selectedAnswerId]->getPonderation();
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreMultipleAnswer(array $answers, array $rows): float
    {
        $score = 0.0;
        foreach ($this->getSavedAnswerIds($rows) as $answerId) {
            if (!isset($answers[$answerId])) {
                continue;
            }

            $score += $answers[$answerId]->getPonderation();
        }

        return $score;
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreMultipleCombination(CQuizQuestion $question, array $answers, array $rows): float
    {
        $selectedAnswerIds = array_flip($this->getSavedAnswerIds($rows));
        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $isCorrect = 1 === (int) $answer->getCorrect();
            $isSelected = isset($selectedAnswerIds[$answerId]);
            if ($isCorrect !== $isSelected) {
                return 0.0;
            }
        }

        $firstAnswer = reset($answers);
        if ($firstAnswer instanceof CQuizAnswer && 0.0 !== $firstAnswer->getPonderation()) {
            return $firstAnswer->getPonderation();
        }

        return (float) $question->getPonderation();
    }

    /**
     * @param array<int, CQuizAnswer>         $answers
     * @param array<int, CQuizQuestionOption> $options
     * @param array<int, TrackEAttempt>       $rows
     */
    private function scoreTrueFalseAnswer(CQuizQuestion $question, array $answers, array $options, array $rows): float
    {
        [$trueScore, $falseScore, $doubtScore] = $this->getTrueFalseScores((string) $question->getExtra());
        $choices = $this->getSavedTrueFalseChoices($rows);
        $score = 0.0;

        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $studentChoice = $choices[$answerId] ?? 0;
            if (0 >= $studentChoice) {
                $score += $doubtScore;
                continue;
            }

            if ($this->isTrueFalseChoiceCorrect($studentChoice, (int) $answer->getCorrect(), $options)) {
                $score += $trueScore;
                continue;
            }

            $optionTitle = $this->getTrueFalseOptionTitle($studentChoice, $options);
            $score += \in_array($optionTitle, ["Don't know", 'DoubtScore'], true) ? $doubtScore : $falseScore;
        }

        return $score;
    }

    /**
     * @param array<int, CQuizAnswer>         $answers
     * @param array<int, CQuizQuestionOption> $options
     * @param array<int, TrackEAttempt>       $rows
     */
    private function scoreTrueFalseCombination(CQuizQuestion $question, array $answers, array $options, array $rows): float
    {
        $choices = $this->getSavedTrueFalseChoices($rows);
        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            if (!$this->isTrueFalseChoiceCorrect($choices[$answerId] ?? 0, (int) $answer->getCorrect(), $options)) {
                return 0.0;
            }
        }

        $firstAnswer = reset($answers);
        if ($firstAnswer instanceof CQuizAnswer && 0.0 !== $firstAnswer->getPonderation()) {
            return $firstAnswer->getPonderation();
        }

        return (float) $question->getPonderation();
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function getTrueFalseScores(string $extra): array
    {
        if ('' === trim($extra)) {
            return [1.0, -0.5, 0.0];
        }

        $parts = explode(':', $extra);

        return [
            isset($parts[0]) ? (float) trim($parts[0]) : 1.0,
            isset($parts[1]) ? (float) trim($parts[1]) : -0.5,
            isset($parts[2]) ? (float) trim($parts[2]) : 0.0,
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, int>
     */
    private function getSavedTrueFalseChoices(array $rows): array
    {
        $choices = [];
        foreach ($rows as $row) {
            $parts = explode(':', (string) $row->getAnswer());
            $answerId = isset($parts[0]) ? (int) $parts[0] : 0;
            $optionId = isset($parts[1]) ? (int) $parts[1] : 0;
            if (0 < $answerId && 0 < $optionId) {
                $choices[$answerId] = $optionId;
            }
        }

        return $choices;
    }
    /**
     * @param array<int, CQuizQuestionOption> $options
     */
    private function isTrueFalseChoiceCorrect(int $studentChoice, int $correctChoice, array $options): bool
    {
        if (0 >= $studentChoice || 0 >= $correctChoice) {
            return false;
        }

        if ($studentChoice === $correctChoice) {
            return true;
        }

        $studentPosition = $this->getTrueFalseOptionPosition($studentChoice, $options);
        $correctPosition = $this->getTrueFalseOptionPosition($correctChoice, $options);

        return 0 < $studentPosition && $studentPosition === $correctPosition;
    }

    /**
     * @param array<int, CQuizQuestionOption> $options
     */
    private function getTrueFalseOptionPosition(int $choice, array $options): int
    {
        $option = $options[$choice] ?? null;
        if ($option instanceof CQuizQuestionOption) {
            return (int) $option->getPosition();
        }

        foreach ($options as $candidate) {
            if (!$candidate instanceof CQuizQuestionOption) {
                continue;
            }

            if ((int) $candidate->getPosition() === $choice) {
                return (int) $candidate->getPosition();
            }
        }

        return $choice;
    }

    /**
     * @param array<int, CQuizQuestionOption> $options
     */
    private function getTrueFalseOptionTitle(int $choice, array $options): string
    {
        $option = $options[$choice] ?? null;
        if ($option instanceof CQuizQuestionOption) {
            return (string) $option->getTitle();
        }

        foreach ($options as $candidate) {
            if (!$candidate instanceof CQuizQuestionOption) {
                continue;
            }

            if ((int) $candidate->getPosition() === $choice) {
                return (string) $candidate->getTitle();
            }
        }

        return '';
    }


    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreFillBlanks(CQuiz $quiz, CQuizQuestion $question, array $answers, array $rows): float
    {
        $row = $rows[0] ?? null;
        if (!$row instanceof TrackEAttempt) {
            return 0.0;
        }

        $answer = reset($answers);
        if (!$answer instanceof CQuizAnswer) {
            return 0.0;
        }

        $teacherInfo = $this->parseFillBlankAnswer($answer->getAnswer(), false);
        $studentInfo = $this->parseFillBlankAnswer($row->getAnswer(), true);
        $caseInsensitive = 'case:false' === (string) $question->getExtra();
        $studentScores = [];
        $score = 0.0;
        $blankCount = \count($teacherInfo['words']);

        for ($index = 0; $index < $blankCount; ++$index) {
            $correctAnswer = (string) ($teacherInfo['words'][$index] ?? '');
            $studentAnswer = (string) ($studentInfo['student_answer'][$index] ?? '');
            $isCorrect = $this->isFillBlankStudentAnswerGood($studentAnswer, $correctAnswer, $caseInsensitive);
            $studentScores[$index] = $isCorrect ? '1' : '0';
            if ($isCorrect) {
                $score += (float) ($teacherInfo['weighting'][$index] ?? 0.0);
            }
        }

        if (self::FILL_IN_BLANKS_COMBINATION === (int) $question->getType()) {
            $score = $blankCount > 0 && !\in_array('0', $studentScores, true) ? (float) $question->getPonderation() : 0.0;
        }

        $row->setAnswer($this->rebuildFillBlankStudentAnswer($teacherInfo, $studentInfo['student_answer'], $studentScores));

        return $score;
    }

    /**
     * @return array{
     *     text: string,
     *     system_string: string,
     *     words_count: int,
     *     words: array<int, string>,
     *     words_with_bracket: array<int, string>,
     *     weighting: array<int, string>,
     *     common_words: array<int, string>,
     *     student_answer: array<int, string>,
     *     student_score: array<int, string>,
     *     blank_separator_start: string,
     *     blank_separator_end: string
     * }
     */
    private function parseFillBlankAnswer(string $answer, bool $isStudentAnswer): array
    {
        $parts = [];
        if (1 === preg_match('/(.*)::(.*)$/s', $answer, $matches)) {
            $parts = [(string) ($matches[1] ?? ''), (string) ($matches[2] ?? '')];
        } else {
            $parts = ['', ''];
        }

        $systemString = $parts[1];
        $systemParts = explode('@', $systemString, 2);
        $details = explode(':', (string) ($systemParts[0] ?? ''));
        $weighting = '' !== (string) ($details[0] ?? '') ? explode(',', (string) $details[0]) : [];
        $separatorNumber = \count($details) >= 3 ? (int) ($details[2] ?? 0) : 0;
        [$start, $end] = $this->getFillBlankSeparators($separatorNumber);
        $startPattern = preg_quote($start, '/');
        $endPattern = preg_quote($end, '/');
        $wordMatches = [];
        preg_match_all('/'.$startPattern.'[^'.$endPattern.']*'.$endPattern.'/', $parts[0], $wordMatches);
        $wordsWithBracket = \is_array($wordMatches[0] ?? null) ? $wordMatches[0] : [];
        $words = [];
        foreach ($wordsWithBracket as $word) {
            $words[] = trim((string) $word, $start.$end);
        }

        $commonWordsString = preg_replace('/'.$startPattern.'[^'.$endPattern.']*'.$endPattern.'/', '::', $parts[0]);
        if (!\is_string($commonWordsString)) {
            $commonWordsString = '';
        }

        $studentAnswer = [];
        $studentScore = [];
        if ($isStudentAnswer) {
            $baseWords = [];
            $baseWordsWithBracket = [];
            $count = \count($words);
            for ($index = 0; $index < $count; ++$index) {
                $baseWordsWithBracket[] = $wordsWithBracket[$index] ?? '';
                $baseWords[] = $words[$index] ?? '';
                ++$index;
                $studentAnswer[] = $words[$index] ?? '';
                ++$index;
                $studentScore[] = $words[$index] ?? '0';
            }
            $words = $baseWords;
            $wordsWithBracket = $baseWordsWithBracket;
            $commonWordsString = preg_replace('/::::::/', '::', $commonWordsString) ?: '';
        }

        return [
            'text' => $parts[0],
            'system_string' => $systemString,
            'words_count' => \count($words),
            'words' => $words,
            'words_with_bracket' => $wordsWithBracket,
            'weighting' => $weighting,
            'common_words' => explode('::', $commonWordsString),
            'student_answer' => $studentAnswer,
            'student_score' => $studentScore,
            'blank_separator_start' => $start,
            'blank_separator_end' => $end,
        ];
    }

    /**
     * @param array{
     *     common_words: array<int, string>,
     *     words_with_bracket: array<int, string>,
     *     system_string: string,
     *     blank_separator_start: string,
     *     blank_separator_end: string
     * } $teacherInfo
     * @param array<int, string> $studentAnswers
     * @param array<int, string> $studentScores
     */
    private function rebuildFillBlankStudentAnswer(array $teacherInfo, array $studentAnswers, array $studentScores): string
    {
        $start = (string) $teacherInfo['blank_separator_start'];
        $end = (string) $teacherInfo['blank_separator_end'];
        $commonWords = $teacherInfo['common_words'];
        $result = '';
        $count = \count($teacherInfo['words_with_bracket']);

        for ($index = 0; $index < $count; ++$index) {
            $result .= (string) ($commonWords[$index] ?? '');
            $result .= (string) ($teacherInfo['words_with_bracket'][$index] ?? '');
            $result .= $start.(string) ($studentAnswers[$index] ?? '').$end;
            $result .= $start.(string) ($studentScores[$index] ?? '0').$end;
        }

        $result .= (string) ($commonWords[$count] ?? '');
        $result .= '::'.(string) $teacherInfo['system_string'];

        return $result;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getFillBlankSeparators(int $separator): array
    {
        return match ($separator) {
            1 => ['{', '}'],
            2 => ['(', ')'],
            3 => ['*', '*'],
            4 => ['#', '#'],
            5 => ['%', '%'],
            6 => ['$', '$'],
            default => ['[', ']'],
        };
    }

    private function isFillBlankStudentAnswerGood(string $studentAnswer, string $correctAnswer, bool $caseInsensitive): bool
    {
        $normalizedStudentAnswer = $caseInsensitive ? mb_strtolower($studentAnswer) : $studentAnswer;

        if (false !== strpos($correctAnswer, '|') && false === strpos($correctAnswer, '||')) {
            $menuAnswers = array_map([$this, 'trimFillBlankOption'], explode('|', $correctAnswer));
            $firstAnswer = (string) ($menuAnswers[0] ?? '');
            $normalizedFirstAnswer = $caseInsensitive ? mb_strtolower($firstAnswer) : $firstAnswer;

            return $normalizedStudentAnswer === $normalizedFirstAnswer || $normalizedStudentAnswer === sha1($normalizedFirstAnswer);
        }

        if (false !== strpos($correctAnswer, '||')) {
            $answers = array_map([$this, 'trimFillBlankOption'], preg_split('/\|\|/', $correctAnswer) ?: []);
            foreach ($answers as $answer) {
                $candidate = $caseInsensitive ? mb_strtolower($answer) : $answer;
                if ($normalizedStudentAnswer === $candidate) {
                    return true;
                }
            }

            return false;
        }

        $normalizedCorrectAnswer = $caseInsensitive ? mb_strtolower($this->trimFillBlankOption($correctAnswer)) : $this->trimFillBlankOption($correctAnswer);

        return $normalizedStudentAnswer === $normalizedCorrectAnswer;
    }

    private function trimFillBlankOption(string $value): string
    {
        return trim(html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreMatchingAnswer(array $answers, array $rows): float
    {
        $choices = $this->getSavedMatchingChoices($rows);
        $score = 0.0;
        foreach ($answers as $answer) {
            if (0 === (int) $answer->getCorrect()) {
                continue;
            }

            $answerId = (int) $answer->getIid();
            if (($choices[$answerId] ?? 0) === (int) $answer->getCorrect()) {
                $score += $answer->getPonderation();
            }
        }

        return $score;
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreMatchingCombination(CQuizQuestion $question, array $answers, array $rows): float
    {
        $choices = $this->getSavedMatchingChoices($rows);
        $optionCount = 0;
        $correctCount = 0;

        foreach ($answers as $answer) {
            if (0 === (int) $answer->getCorrect()) {
                continue;
            }

            ++$optionCount;
            $answerId = (int) $answer->getIid();
            if (($choices[$answerId] ?? 0) === (int) $answer->getCorrect()) {
                ++$correctCount;
            }
        }

        return 0 < $optionCount && $correctCount === $optionCount ? (float) $question->getPonderation() : 0.0;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, int>
     */
    private function getSavedMatchingChoices(array $rows): array
    {
        $choices = [];
        foreach ($rows as $row) {
            $position = $row->getPosition();
            if (null === $position || 0 >= $position) {
                continue;
            }

            $choices[(int) $position] = (int) $row->getAnswer();
        }

        return $choices;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreManualAnswer(array $rows): float
    {
        $row = $rows[0] ?? null;

        return $row instanceof TrackEAttempt ? (float) $row->getMarks() : 0.0;
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     */
    private function getQuestionWeight(CQuizQuestion $question, array $answers): float
    {
        $type = (int) $question->getType();
        if (\in_array($type, [self::MULTIPLE_ANSWER_COMBINATION, self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE], true)) {
            $firstAnswer = reset($answers);
            if ($firstAnswer instanceof CQuizAnswer && 0.0 !== $firstAnswer->getPonderation()) {
                return $firstAnswer->getPonderation();
            }
        }

        return (float) $question->getPonderation();
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     */
    private function getFirstSavedAnswerId(array $rows): int
    {
        $row = $rows[0] ?? null;

        return $row instanceof TrackEAttempt ? (int) $row->getAnswer() : 0;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, int>
     */
    private function requiresManualCorrection(CQuizQuestion $question): bool
    {
        return \in_array((int) $question->getType(), [self::FREE_ANSWER, self::UPLOAD_ANSWER], true);
    }

    private function getSavedAnswerIds(array $rows): array
    {
        $answerIds = [];
        foreach ($rows as $row) {
            $answerId = (int) $row->getAnswer();
            if (0 < $answerId && !\in_array($answerId, $answerIds, true)) {
                $answerIds[] = $answerId;
            }
        }

        return $answerIds;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     */
    private function updateQuestionAttemptRows(CQuizQuestion $question, array $rows, float $score): void
    {
        foreach ($rows as $row) {
            $row->setMarks($score);
            $row->setTms(new DateTime());
        }

        if ([] === $rows && !$this->requiresManualCorrection($question)) {
            return;
        }
    }

    private function getFinishedAt(TrackEExercise $attempt): DateTime
    {
        $finishedAt = new DateTime();
        $expiredAt = $attempt->getExpiredTimeControl();
        if ($expiredAt instanceof DateTime && $expiredAt < $finishedAt) {
            return $expiredAt;
        }

        return $finishedAt;
    }
}
