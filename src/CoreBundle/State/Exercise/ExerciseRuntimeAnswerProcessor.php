<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeAnswer;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Saves draft answers for simple Vue runtime question types.
 *
 * This processor intentionally writes legacy-compatible track_e_attempt rows only.
 * Final scoring, status changes and results remain delegated to the legacy runtime.
 *
 * @implements ProcessorInterface<ExerciseRuntimeAnswer, ExerciseRuntimeAnswer>
 */
final readonly class ExerciseRuntimeAnswerProcessor implements ProcessorInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const UNIQUE_TYPES = [1, 10, 17];
    private const MULTIPLE_TYPES = [2, 9, 14];
    private const TRUE_FALSE_TYPES = [11, 12];
    private const FILL_BLANK_TYPES = [3, 27];
    private const MATCHING_TYPES = [4, 19, 24, 25];
    private const DROPDOWN_TYPES = [28, 29];
    private const FREE_ANSWER_TYPES = [5];

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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeAnswer
    {
        if (!$data instanceof ExerciseRuntimeAnswer) {
            throw new BadRequestHttpException('Invalid exercise runtime answer payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canSaveDraftAnswers()) {
            throw new AccessDeniedHttpException('You are not allowed to save answers for this exercise.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
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
        $attempt = $this->getIncompleteAttempt($attemptId, $quiz, $course, $session, $user);
        $question = $this->getQuestionFromExercise($questionId, $quiz);

        if (!$question instanceof CQuizQuestion) {
            throw new NotFoundHttpException('The requested question was not found in this exercise.');
        }

        if (!$this->questionBelongsToAttempt($questionId, $attempt)) {
            throw new AccessDeniedHttpException('The requested question does not belong to this attempt.');
        }

        $rows = $this->buildDraftRows($question, $data->answer, max(0, (int) $data->secondsSpent));
        $this->deletePreviousDraftRows($attempt, $questionId);

        foreach ($rows as $row) {
            $attemptRow = (new TrackEAttempt())
                ->setTrackEExercise($attempt)
                ->setUser($user)
                ->setQuestionId($questionId)
                ->setAnswer($row['answer'])
                ->setTeacherComment('')
                ->setMarks(0.0)
                ->setPosition($row['position'])
                ->setTms(new DateTime())
                ->setSecondsSpent($row['secondsSpent'])
            ;
            $this->entityManager->persist($attemptRow);
        }

        $this->entityManager->flush();

        $response = new ExerciseRuntimeAnswer();
        $response->exerciseId = $exerciseId;
        $response->attemptId = $attemptId;
        $response->questionId = $questionId;
        $response->success = true;
        $response->message = [] === $rows ? 'Draft answer cleared' : 'Draft answer saved';
        $response->savedAnswer = $this->getSavedAnswerRows($attemptId, $questionId);
        $response->answeredQuestionIds = $this->getAnsweredQuestionIds($attemptId);
        $response->answeredCount = \count($response->answeredQuestionIds);
        $response->canFinish = false;

        return $response;
    }

    private function canSaveDraftAnswers(): bool
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
        $now = new DateTimeImmutable();
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

    private function questionBelongsToAttempt(int $questionId, TrackEExercise $attempt): bool
    {
        $attemptQuestionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        if ([] === $attemptQuestionIds) {
            return true;
        }

        return \in_array($questionId, $attemptQuestionIds, true);
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
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildDraftRows(CQuizQuestion $question, mixed $answer, int $secondsSpent): array
    {
        $type = (int) $question->getType();
        $payload = $this->normalizePayload($answer);

        if (\in_array($type, self::UNIQUE_TYPES, true)) {
            return $this->buildUniqueRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::MULTIPLE_TYPES, true)) {
            return $this->buildMultipleRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::TRUE_FALSE_TYPES, true)) {
            return $this->buildTrueFalseRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::FILL_BLANK_TYPES, true)) {
            return $this->buildFillBlankRows($question, $payload, $secondsSpent);
        }

        if (\in_array($type, self::MATCHING_TYPES, true)) {
            return $this->buildMatchingRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::DROPDOWN_TYPES, true)) {
            return $this->buildDropdownRows($payload, $secondsSpent);
        }

        if (\in_array($type, self::FREE_ANSWER_TYPES, true)) {
            return $this->buildFreeAnswerRows($payload, $secondsSpent);
        }

        throw new BadRequestHttpException('This question type is not supported by the draft answer processor yet.');
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePayload(mixed $answer): array
    {
        if (\is_array($answer)) {
            return $answer;
        }

        if (null === $answer) {
            return [];
        }

        return ['value' => $answer];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildUniqueRows(array $payload, int $secondsSpent): array
    {
        $choiceId = $this->toPositiveInt($payload['choice'] ?? $payload['value'] ?? null);

        return 0 < $choiceId ? [['answer' => (string) $choiceId, 'position' => 0, 'secondsSpent' => $secondsSpent]] : [];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildMultipleRows(array $payload, int $secondsSpent): array
    {
        $choices = $this->toPositiveIntList($payload['choices'] ?? $payload['value'] ?? []);
        $rows = [];
        foreach ($choices as $position => $choiceId) {
            $rows[] = ['answer' => (string) $choiceId, 'position' => $position, 'secondsSpent' => $secondsSpent];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildTrueFalseRows(array $payload, int $secondsSpent): array
    {
        $values = $payload['trueFalse'] ?? $payload['value'] ?? [];
        if (!\is_array($values)) {
            return [];
        }

        $rows = [];
        $position = 0;
        foreach ($values as $answerId => $optionValue) {
            $safeAnswerId = $this->toPositiveInt($answerId);
            $safeOptionValue = $this->toPositiveInt($optionValue);
            if (0 >= $safeAnswerId || 0 >= $safeOptionValue) {
                continue;
            }

            $rows[] = [
                'answer' => $safeAnswerId.':'.$safeOptionValue,
                'position' => $position,
                'secondsSpent' => $secondsSpent,
            ];
            ++$position;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildFillBlankRows(CQuizQuestion $question, array $payload, int $secondsSpent): array
    {
        $answer = $this->getFirstAnswer($question);
        if (!$answer instanceof CQuizAnswer) {
            return [];
        }

        $blankValues = $payload['blanks'] ?? $payload['value'] ?? [];
        if (!\is_array($blankValues)) {
            return [];
        }

        $encodedAnswer = $this->buildStudentFillBlankAnswer($answer->getAnswer(), $blankValues);
        if ('' === $encodedAnswer) {
            return [];
        }

        return [['answer' => $encodedAnswer, 'position' => 0, 'secondsSpent' => $secondsSpent]];
    }

    /**
     * @param array<string|int, mixed> $blankValues
     */
    private function buildStudentFillBlankAnswer(string $teacherAnswer, array $blankValues): string
    {
        $parts = explode('::', $teacherAnswer, 2);
        $text = (string) ($parts[0] ?? '');
        $systemString = (string) ($parts[1] ?? '');
        $separator = $this->getFillBlankSeparator($systemString);
        [$start, $end] = $this->getFillBlankSeparators($separator);
        $pattern = '/'.preg_quote($start, '/').'(.*?)'.preg_quote($end, '/').'/s';
        $blankIndex = 0;

        $studentText = preg_replace_callback(
            $pattern,
            function (array $matches) use (&$blankIndex, $blankValues, $start, $end): string {
                ++$blankIndex;
                $correctAnswer = (string) ($matches[1] ?? '');
                $studentAnswer = $this->escapeFillBlankValue($blankValues[$blankIndex] ?? '');

                return $start.$correctAnswer.$end.$start.$studentAnswer.$end.$start.'0'.$end;
            },
            $text,
        );

        if (!\is_string($studentText)) {
            return '';
        }

        return $studentText.'::'.$systemString;
    }

    private function getFillBlankSeparator(string $systemString): int
    {
        $systemParts = explode('@', $systemString, 2);
        $details = explode(':', (string) ($systemParts[0] ?? ''));

        return \count($details) >= 3 ? max(0, (int) ($details[2] ?? 0)) : 0;
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

    private function escapeFillBlankValue(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildMatchingRows(array $payload, int $secondsSpent): array
    {
        $values = $payload['matching'] ?? $payload['value'] ?? [];
        if (!\is_array($values)) {
            return [];
        }

        $rows = [];
        foreach ($values as $promptId => $optionId) {
            $safePromptId = $this->toPositiveInt($promptId);
            $safeOptionId = $this->toPositiveInt($optionId);
            if (0 >= $safePromptId || 0 >= $safeOptionId) {
                continue;
            }

            $rows[] = [
                'answer' => (string) $safeOptionId,
                'position' => $safePromptId,
                'secondsSpent' => $secondsSpent,
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildDropdownRows(array $payload, int $secondsSpent): array
    {
        if (isset($payload['choices'])) {
            return $this->buildMultipleRows(['choices' => $payload['choices']], $secondsSpent);
        }

        $choiceId = $this->toPositiveInt($payload['dropdown'] ?? $payload['value'] ?? null);

        return 0 < $choiceId ? [['answer' => (string) $choiceId, 'position' => 0, 'secondsSpent' => $secondsSpent]] : [];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<int, array{answer: string, position: int, secondsSpent: int}>
     */
    private function buildFreeAnswerRows(array $payload, int $secondsSpent): array
    {
        $text = (string) ($payload['text'] ?? $payload['value'] ?? '');

        return '' !== $text ? [['answer' => $text, 'position' => 0, 'secondsSpent' => $secondsSpent]] : [];
    }

    /**
     * @return array<int, int>
     */
    private function toPositiveIntList(mixed $value): array
    {
        if (!\is_array($value)) {
            $value = [$value];
        }

        $result = [];
        foreach ($value as $item) {
            $integer = $this->toPositiveInt($item);
            if (0 < $integer && !\in_array($integer, $result, true)) {
                $result[] = $integer;
            }
        }

        return $result;
    }

    private function toPositiveInt(mixed $value): int
    {
        if (null === $value || '' === $value) {
            return 0;
        }

        return max(0, (int) $value);
    }

    private function getFirstAnswer(CQuizQuestion $question): ?CQuizAnswer
    {
        $answer = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->orderBy('answer.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $answer instanceof CQuizAnswer ? $answer : null;
    }

    private function deletePreviousDraftRows(TrackEExercise $attempt, int $questionId): void
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->andWhere('saved.questionId = :questionId')
            ->setParameter('attemptId', (int) $attempt->getExeId(), Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        foreach ($rows as $row) {
            if ($row instanceof TrackEAttempt) {
                $this->entityManager->remove($row);
            }
        }
    }

    /**
     * @return array<int, array{answer: string, position: int|null}>
     */
    private function getSavedAnswerRows(int $attemptId, int $questionId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('saved.answer AS answer')
            ->addSelect('saved.position AS position')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->andWhere('saved.questionId = :questionId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->orderBy('saved.position', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $result[] = [
                'answer' => (string) ($row['answer'] ?? ''),
                'position' => null !== ($row['position'] ?? null) ? (int) $row['position'] : null,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, int>
     */
    private function getAnsweredQuestionIds(int $attemptId): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT saved.questionId AS questionId')
            ->from(TrackEAttempt::class, 'saved')
            ->andWhere('IDENTITY(saved.trackExercise) = :attemptId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->orderBy('saved.questionId', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [];
        foreach ($rows as $row) {
            if (\is_array($row)) {
                $result[] = (int) ($row['questionId'] ?? 0);
            }
        }

        return array_values(array_filter($result));
    }
}
