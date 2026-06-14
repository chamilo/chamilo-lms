<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Legacy-equivalent counters for answer distributions that cannot be counted by
 * the simple track_e_attempt.answer = c_quiz_answer.iid rule.
 */
final readonly class ExerciseSpecialQuestionReportCounterService
{
    private const FILL_IN_BLANKS = 3;
    private const MATCHING = 4;
    private const HOT_SPOT = 6;
    private const MATCHING_DRAGGABLE = 19;
    private const MATCHING_COMBINATION = 24;
    private const MATCHING_DRAGGABLE_COMBINATION = 25;
    private const HOT_SPOT_COMBINATION = 26;
    private const FILL_IN_BLANKS_COMBINATION = 27;

    /**
     * @var array<int, true>
     */
    private const FILL_BLANK_TYPES = [
        self::FILL_IN_BLANKS => true,
        self::FILL_IN_BLANKS_COMBINATION => true,
    ];

    /**
     * @var array<int, true>
     */
    private const MATCHING_TYPES = [
        self::MATCHING => true,
        self::MATCHING_DRAGGABLE => true,
        self::MATCHING_COMBINATION => true,
        self::MATCHING_DRAGGABLE_COMBINATION => true,
    ];

    /**
     * @var array<int, true>
     */
    private const HOTSPOT_TYPES = [
        self::HOT_SPOT => true,
        self::HOT_SPOT_COMBINATION => true,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param array<int, array<string, mixed>> $questions
     *
     * @return array<int, array{answers: array<int, array<string, mixed>>, totalSelections: int, countingAvailable: bool}>
     */
    public function buildDistributions(CQuiz $quiz, Course $course, ?Session $session, array $questions): array
    {
        $distributions = [];

        foreach ($questions as $questionId => $question) {
            $type = (int) ($question['type'] ?? 0);
            $answers = $question['answers'] ?? [];
            if (!\is_array($answers)) {
                $answers = [];
            }

            if (isset(self::FILL_BLANK_TYPES[$type])) {
                $distribution = $this->buildFillBlankDistribution($quiz, $course, $session, (int) $questionId, $question, $answers);
            } elseif (isset(self::MATCHING_TYPES[$type])) {
                $distribution = $this->buildMatchingDistribution($quiz, $course, $session, (int) $questionId, $answers);
            } elseif (isset(self::HOTSPOT_TYPES[$type])) {
                $distribution = $this->buildHotspotDistribution($quiz, $course, $session, (int) $questionId, $answers);
            } else {
                $distribution = null;
            }

            if (null !== $distribution) {
                $distributions[(int) $questionId] = $distribution;
            }
        }

        return $distributions;
    }

    /**
     * @param array<string, mixed>              $question
     * @param array<int, array<string, mixed>> $answers
     *
     * @return array{answers: array<int, array<string, mixed>>, totalSelections: int, countingAvailable: bool}|null
     */
    private function buildFillBlankDistribution(CQuiz $quiz, Course $course, ?Session $session, int $questionId, array $question, array $answers): ?array
    {
        $sourceAnswer = $answers[0] ?? null;
        if (!\is_array($sourceAnswer)) {
            return null;
        }

        $teacherInfo = $this->parseFillBlankAnswer((string) ($sourceAnswer['answer'] ?? ''), false);
        $blankLabels = $teacherInfo['words_with_bracket'];
        if ([] === $blankLabels) {
            return [
                'answers' => [],
                'totalSelections' => 0,
                'countingAvailable' => true,
            ];
        }

        $caseInsensitive = 'case:false' === (string) ($question['extra'] ?? '');
        $userResults = [];
        foreach ($this->getCompletedAttemptAnswerRows($quiz, $course, $session, $questionId) as $attemptRow) {
            $userId = (int) ($attemptRow['userId'] ?? 0);
            if (0 >= $userId) {
                continue;
            }

            $studentInfo = $this->parseFillBlankAnswer((string) ($attemptRow['answer'] ?? ''), true);
            foreach ($teacherInfo['words'] as $index => $correctAnswer) {
                $studentAnswer = (string) ($studentInfo['student_answer'][$index] ?? '');
                $studentScore = (string) ($studentInfo['student_score'][$index] ?? '');

                if ('' !== $studentScore) {
                    $userResults[$userId][$index] = '1' === $studentScore ? 0 : (-2 === $this->normalizeFillBlankState($studentAnswer) ? -2 : -1);
                    continue;
                }

                if ('' === trim($studentAnswer)) {
                    if (!isset($userResults[$userId][$index])) {
                        $userResults[$userId][$index] = -2;
                    }
                    continue;
                }

                $userResults[$userId][$index] = $this->isFillBlankStudentAnswerGood($studentAnswer, (string) $correctAnswer, $caseInsensitive) ? 0 : -1;
            }
        }

        $rows = [];
        $totalSelections = 0;
        $baseAnswerId = (string) ($sourceAnswer['answerId'] ?? $sourceAnswer['id'] ?? $questionId);
        foreach ($blankLabels as $index => $label) {
            $selectedCount = 0;
            foreach ($userResults as $result) {
                if (0 === (int) ($result[$index] ?? -2)) {
                    ++$selectedCount;
                }
            }

            $totalSelections += $selectedCount;
            $rows[] = [
                'id' => sprintf('%d-blank-%d', $questionId, $index + 1),
                'answerId' => sprintf('%s:%d', $baseAnswerId, $index + 1),
                'answer' => $this->stripFillBlankBrackets((string) $label, (string) $teacherInfo['blank_separator_start'], (string) $teacherInfo['blank_separator_end']),
                'correct' => false,
                'correctValue' => 0,
                'score' => round((float) ($teacherInfo['weighting'][$index] ?? 0.0), 2),
                'position' => $index + 1,
                'selectedCount' => $selectedCount,
                'selectedPercentage' => 0.0,
            ];
        }

        return $this->withPercentages($rows, $totalSelections);
    }

    private function normalizeFillBlankState(string $studentAnswer): int
    {
        return '' === trim($studentAnswer) ? -2 : -1;
    }

    /**
     * @param array<int, array<string, mixed>> $answers
     *
     * @return array{answers: array<int, array<string, mixed>>, totalSelections: int, countingAvailable: bool}
     */
    private function buildMatchingDistribution(CQuiz $quiz, Course $course, ?Session $session, int $questionId, array $answers): array
    {
        $selectedCounts = $this->getAnswerValueCounts($quiz, $course, $session, $questionId);
        $answersByPosition = [];
        foreach ($answers as $answer) {
            $position = (int) ($answer['position'] ?? 0);
            if (0 < $position) {
                $answersByPosition[$position] = $answer;
            }
        }

        $rows = [];
        $totalSelections = 0;
        foreach ($answers as $answer) {
            $correctValue = (int) ($answer['correctValue'] ?? 0);
            if (0 !== $correctValue) {
                continue;
            }

            $answerId = (int) ($answer['answerId'] ?? 0);
            if (0 >= $answerId) {
                continue;
            }

            $sourceAnswer = $this->findMatchingSourceAnswer($answer, $answersByPosition);
            $selectedCount = (int) ($selectedCounts[(string) $answerId] ?? 0);
            $totalSelections += $selectedCount;
            $rows[] = [
                'id' => $answerId,
                'answerId' => $answerId,
                'answer' => $this->formatMatchingAnswerLabel($sourceAnswer, (string) ($answer['answer'] ?? '')),
                'correct' => false,
                'correctValue' => 0,
                'score' => (float) ($sourceAnswer['score'] ?? $answer['score'] ?? 0.0),
                'position' => (int) ($answer['position'] ?? 0),
                'selectedCount' => $selectedCount,
                'selectedPercentage' => 0.0,
            ];
        }

        return $this->withPercentages($rows, $totalSelections);
    }

    /**
     * @param array<string, mixed>              $targetAnswer
     * @param array<int, array<string, mixed>> $answersByPosition
     *
     * @return array<string, mixed>
     */
    private function findMatchingSourceAnswer(array $targetAnswer, array $answersByPosition): array
    {
        $targetPosition = (int) ($targetAnswer['position'] ?? 0);
        foreach ($answersByPosition as $answer) {
            if ((int) ($answer['correctValue'] ?? 0) === $targetPosition) {
                return $answer;
            }
        }

        return [];
    }

    private function formatMatchingAnswerLabel(array $sourceAnswer, string $targetAnswer): string
    {
        $source = trim(strip_tags(html_entity_decode((string) ($sourceAnswer['answer'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $target = trim(strip_tags(html_entity_decode($targetAnswer, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ('' === $source) {
            return $target;
        }
        if ('' === $target) {
            return $source;
        }

        return sprintf('%s → %s', $source, $target);
    }

    /**
     * @param array<int, array<string, mixed>> $answers
     *
     * @return array{answers: array<int, array<string, mixed>>, totalSelections: int, countingAvailable: bool}|null
     */
    private function buildHotspotDistribution(CQuiz $quiz, Course $course, ?Session $session, int $questionId, array $answers): ?array
    {
        $answerIds = [];
        foreach ($answers as $answer) {
            $answerId = (int) ($answer['answerId'] ?? 0);
            if (0 < $answerId) {
                $answerIds[] = $answerId;
            }
        }
        if ([] === $answerIds) {
            return [
                'answers' => [],
                'totalSelections' => 0,
                'countingAvailable' => true,
            ];
        }

        $selectedCounts = $this->getHotspotCorrectAnswerCounts($quiz, $course, $session, $questionId, $answerIds);
        if (null === $selectedCounts) {
            return null;
        }

        $rows = [];
        $totalSelections = 0;
        foreach ($answers as $answer) {
            $answerId = (int) ($answer['answerId'] ?? 0);
            if (0 >= $answerId) {
                continue;
            }

            $selectedCount = (int) ($selectedCounts[$answerId] ?? 0);
            $totalSelections += $selectedCount;
            $rows[] = [
                'id' => $answerId,
                'answerId' => $answerId,
                'answer' => (string) ($answer['answer'] ?? ''),
                'correct' => false,
                'correctValue' => (int) ($answer['correctValue'] ?? 0),
                'score' => (float) ($answer['score'] ?? 0.0),
                'position' => (int) ($answer['position'] ?? 0),
                'selectedCount' => $selectedCount,
                'selectedPercentage' => 0.0,
            ];
        }

        return $this->withPercentages($rows, $totalSelections);
    }

    /**
     * @return array<int, array{answer: string, userId: int, attemptId: int}>
     */
    private function getCompletedAttemptAnswerRows(CQuiz $quiz, Course $course, ?Session $session, int $questionId): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt.answer AS answer')
            ->addSelect('IDENTITY(trackedExercise.user) AS userId')
            ->addSelect('trackedExercise.exeId AS attemptId')
            ->from(TrackEAttempt::class, 'attempt')
            ->innerJoin('attempt.trackExercise', 'trackedExercise')
            ->andWhere('IDENTITY(trackedExercise.quiz) = :exerciseId')
            ->andWhere('IDENTITY(trackedExercise.course) = :courseId')
            ->andWhere('trackedExercise.status = :completedStatus')
            ->andWhere('attempt.questionId = :questionId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('completedStatus', '', Types::STRING)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->orderBy('userId', 'ASC')
            ->addOrderBy('attemptId', 'ASC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(trackedExercise.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('trackedExercise.session IS NULL');
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @return array<string, int>
     */
    private function getAnswerValueCounts(CQuiz $quiz, Course $course, ?Session $session, int $questionId): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt.answer AS answerValue')
            ->addSelect('COUNT(attempt.id) AS selectedCount')
            ->from(TrackEAttempt::class, 'attempt')
            ->innerJoin('attempt.trackExercise', 'trackedExercise')
            ->andWhere('IDENTITY(trackedExercise.quiz) = :exerciseId')
            ->andWhere('IDENTITY(trackedExercise.course) = :courseId')
            ->andWhere('trackedExercise.status = :completedStatus')
            ->andWhere('attempt.questionId = :questionId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('completedStatus', '', Types::STRING)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->groupBy('attempt.answer')
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
            $answerValue = trim((string) ($row['answerValue'] ?? ''));
            if ('' === $answerValue) {
                continue;
            }

            $counts[$answerValue] = (int) ($row['selectedCount'] ?? 0);
        }

        return $counts;
    }

    /**
     * @param array<int, int> $answerIds
     *
     * @return array<int, int>|null
     */
    private function getHotspotCorrectAnswerCounts(CQuiz $quiz, Course $course, ?Session $session, int $questionId, array $answerIds): ?array
    {
        $tableName = $this->getHotspotTableName();
        if (null === $tableName) {
            return null;
        }

        $connection = $this->entityManager->getConnection();
        $queryBuilder = $connection->createQueryBuilder()
            ->select('hotspot.hotspot_answer_id AS answer_id')
            ->addSelect('COUNT(DISTINCT tracked_exercise.exe_user_id) AS selected_count')
            ->from($tableName, 'hotspot')
            ->innerJoin('hotspot', 'track_e_exercises', 'tracked_exercise', 'tracked_exercise.exe_id = hotspot.hotspot_exe_id')
            ->andWhere('tracked_exercise.exe_exo_id = :exerciseId')
            ->andWhere('tracked_exercise.c_id = :courseId')
            ->andWhere('tracked_exercise.status = :completedStatus')
            ->andWhere('hotspot.hotspot_question_id = :questionId')
            ->andWhere('hotspot.hotspot_answer_id IN (:answerIds)')
            ->andWhere('hotspot.hotspot_correct = 1')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('completedStatus', '', Types::STRING)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setParameter('answerIds', array_values(array_unique($answerIds)), ArrayParameterType::INTEGER)
            ->groupBy('hotspot.hotspot_answer_id')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('tracked_exercise.session_id = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('tracked_exercise.session_id IS NULL');
        }

        $counts = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
            $counts[(int) ($row['answer_id'] ?? 0)] = (int) ($row['selected_count'] ?? 0);
        }

        return $counts;
    }

    private function getHotspotTableName(): ?string
    {
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        foreach (['track_e_hotspot', 'track_e_hotspots'] as $tableName) {
            if ($schemaManager->tablesExist([$tableName])) {
                return $tableName;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array{answers: array<int, array<string, mixed>>, totalSelections: int, countingAvailable: bool}
     */
    private function withPercentages(array $rows, int $totalSelections): array
    {
        if (0 < $totalSelections) {
            foreach ($rows as &$row) {
                $row['selectedPercentage'] = round(((int) ($row['selectedCount'] ?? 0) * 100) / $totalSelections, 2);
            }
            unset($row);
        }

        return [
            'answers' => $rows,
            'totalSelections' => $totalSelections,
            'countingAvailable' => true,
        ];
    }

    /**
     * @return array{
     *     words: array<int, string>,
     *     words_with_bracket: array<int, string>,
     *     weighting: array<int, string>,
     *     student_answer: array<int, string>,
     *     student_score: array<int, string>,
     *     blank_separator_start: string,
     *     blank_separator_end: string
     * }
     */
    private function parseFillBlankAnswer(string $answer, bool $isStudentAnswer): array
    {
        $parts = ['', ''];
        if (1 === preg_match('/(.*)::(.*)$/s', $answer, $matches)) {
            $parts = [(string) ($matches[1] ?? ''), (string) ($matches[2] ?? '')];
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
                $studentScore[] = $words[$index] ?? '';
            }
            $words = $baseWords;
            $wordsWithBracket = $baseWordsWithBracket;
        }

        return [
            'words' => $words,
            'words_with_bracket' => $wordsWithBracket,
            'weighting' => $weighting,
            'student_answer' => $studentAnswer,
            'student_score' => $studentScore,
            'blank_separator_start' => $start,
            'blank_separator_end' => $end,
        ];
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

    private function stripFillBlankBrackets(string $value, string $start, string $end): string
    {
        return trim((string) preg_replace('/^'.preg_quote($start, '/').'(.*)'.preg_quote($end, '/').'$/s', '$1', $value));
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
}
