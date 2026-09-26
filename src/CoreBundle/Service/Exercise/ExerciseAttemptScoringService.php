<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Helpers\ExerciseHotspotGeometryHelper;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Webit\Util\EvalMath\EvalMath;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Recalculates persisted exercise attempt scores using the migrated runtime scoring rules.
 */
final readonly class ExerciseAttemptScoringService
{
    private const int MEDIA_QUESTION = 15;
    private const int UNIQUE_ANSWER = 1;
    private const int MULTIPLE_ANSWER = 2;
    private const int FILL_IN_BLANKS = 3;
    private const int MATCHING = 4;
    private const int FREE_ANSWER = 5;
    private const int HOT_SPOT = 6;
    private const int HOT_SPOT_DELINEATION = 8;
    private const int CALCULATED_ANSWER = 16;
    private const int DRAGGABLE = 18;
    private const int READING_COMPREHENSION = 21;
    private const int PAGE_BREAK = 31;
    private const int ORAL_EXPRESSION = 13;
    private const int UPLOAD_ANSWER = 23;
    private const int ANSWER_IN_OFFICE_DOC = 30;
    private const int ANNOTATION = 20;
    private const int MULTIPLE_ANSWER_COMBINATION = 9;
    private const int UNIQUE_ANSWER_NO_OPTION = 10;
    private const int MULTIPLE_ANSWER_TRUE_FALSE = 11;
    private const int MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE = 12;
    private const int MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY = 22;
    private const int GLOBAL_MULTIPLE_ANSWER = 14;
    private const int UNIQUE_ANSWER_IMAGE = 17;
    private const int MATCHING_DRAGGABLE = 19;
    private const int FILL_IN_BLANKS_COMBINATION = 27;
    private const int MULTIPLE_ANSWER_DROPDOWN_COMBINATION = 28;
    private const int MULTIPLE_ANSWER_DROPDOWN = 29;
    private const int MATCHING_COMBINATION = 24;
    private const int MATCHING_DRAGGABLE_COMBINATION = 25;
    private const int HOT_SPOT_COMBINATION = 26;

    /**
     * @var array<int, string>
     */
    private const array SUPPORTED_TYPE_NAMES = [
        self::UNIQUE_ANSWER => 'Unique answer',
        self::UNIQUE_ANSWER_NO_OPTION => 'Unique answer no option',
        self::UNIQUE_ANSWER_IMAGE => 'Unique answer with images',
        self::MULTIPLE_ANSWER => 'Multiple answer',
        self::GLOBAL_MULTIPLE_ANSWER => 'Global multiple answer',
        self::MULTIPLE_ANSWER_COMBINATION => 'Multiple answer combination',
        self::MULTIPLE_ANSWER_TRUE_FALSE => 'Multiple answer true/false',
        self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => 'Multiple answer combination true/false',
        self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY => 'Multiple answer true/false with degree of certainty',
        self::FILL_IN_BLANKS => 'Fill in blanks',
        self::FILL_IN_BLANKS_COMBINATION => 'Fill in blanks combination',
        self::MATCHING => 'Matching',
        self::MATCHING_DRAGGABLE => 'Matching draggable',
        self::MATCHING_COMBINATION => 'Matching combination',
        self::MATCHING_DRAGGABLE_COMBINATION => 'Matching draggable combination',
        self::HOT_SPOT_COMBINATION => 'Hotspot combination',
        self::MULTIPLE_ANSWER_DROPDOWN => 'Multiple answer dropdown',
        self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION => 'Multiple answer dropdown combination',
        self::FREE_ANSWER => 'Free answer',
        self::HOT_SPOT => 'Hotspot',
        self::CALCULATED_ANSWER => 'Calculated answer',
        self::DRAGGABLE => 'Sequence ordering',
        self::ORAL_EXPRESSION => 'Oral expression',
        self::UPLOAD_ANSWER => 'Upload answer',
        self::ANSWER_IN_OFFICE_DOC => 'Answer in Office document',
        self::ANNOTATION => 'Annotation',
        self::MEDIA_QUESTION => 'Media question',
        self::READING_COMPREHENSION => 'Reading comprehension',
        self::PAGE_BREAK => 'Page break',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExerciseHotspotGeometryHelper $exerciseHotspotGeometryHelper,
    ) {}

    /**
     * @return array{score: float, maxScore: float, questionsToCheck: array<int, int>}
     */
    public function recalculateAttempt(TrackEExercise $attempt, CQuiz $quiz): array
    {
        $questionIds = $this->parseQuestionIds((string) $attempt->getDataTracking());
        if ([] === $questionIds) {
            throw new BadRequestHttpException('The attempt does not contain a persisted question list.');
        }

        $questions = $this->getQuestions($quiz, $questionIds);
        $unsupportedTypes = $this->getUnsupportedQuestionTypes($questions);
        if ([] !== $unsupportedTypes) {
            throw new BadRequestHttpException('This attempt contains question types that are not supported by the migrated scorer yet: '.implode(', ', $unsupportedTypes).'.');
        }

        $totalScore = 0.0;
        $totalWeight = 0.0;
        $pendingQuestionIds = array_flip($this->parseQuestionIds((string) $attempt->getQuestionsToCheck()));
        $questionsToCheck = [];

        foreach ($questionIds as $questionId) {
            $question = $questions[$questionId] ?? null;
            if (!$question instanceof CQuizQuestion) {
                continue;
            }

            $rows = $this->getAttemptRows((int) $attempt->getExeId(), $questionId);
            $answers = $this->getQuestionAnswers($questionId);
            $options = $this->getQuestionOptions($questionId);
            $score = $this->scoreQuestion($quiz, $question, $answers, $options, $rows);
            $weight = $this->getQuestionWeight($question, $answers);

            if (0 === (int) $quiz->getPropagateNeg() && $score < 0) {
                $score = 0.0;
            }

            $this->updateQuestionAttemptRows($question, $rows, $score);

            if ($this->requiresManualCorrection($question) && isset($pendingQuestionIds[$questionId])) {
                $questionsToCheck[] = $questionId;
            }

            $totalScore += $score;
            $totalWeight += $weight;
        }

        if ($totalWeight <= 0.0) {
            $totalWeight = (float) $attempt->getMaxScore();
        }

        $attempt
            ->setScore($totalScore)
            ->setMaxScore($totalWeight)
            ->setQuestionsToCheck(implode(',', $questionsToCheck))
        ;

        $this->syncLearningPathScore($attempt, $totalScore);

        return [
            'score' => $totalScore,
            'maxScore' => $totalWeight,
            'questionsToCheck' => $questionsToCheck,
        ];
    }

    private function syncLearningPathScore(TrackEExercise $attempt, float $score): void
    {
        if ($attempt->getOrigLpId() <= 0 || $attempt->getOrigLpItemId() <= 0 || $attempt->getOrigLpItemViewId() <= 0) {
            return;
        }

        $lpItem = $this->entityManager->getRepository(CLpItem::class)->find($attempt->getOrigLpItemId());
        if (!$lpItem instanceof CLpItem || 'quiz' !== $lpItem->getItemType()) {
            return;
        }

        $lpItemView = $this->entityManager->getRepository(CLpItemView::class)->find($attempt->getOrigLpItemViewId());
        if (!$lpItemView instanceof CLpItemView) {
            return;
        }

        $lpItemView->setScore($score);
    }

    /**
     * @return array<int, int>
     */
    public function parseQuestionIds(string $value): array
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
    public function getQuestions(CQuiz $quiz, array $questionIds): array
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
    public function getUnsupportedQuestionTypes(array $questions): array
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
    public function getAttemptRows(int $attemptId, int $questionId): array
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
    public function getQuestionAnswers(int $questionId): array
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
    public function getQuestionOptions(int $questionId): array
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
    public function scoreQuestion(CQuiz $quiz, CQuizQuestion $question, array $answers, array $options, array $rows): float
    {
        return match ((int) $question->getType()) {
            self::UNIQUE_ANSWER,
            self::UNIQUE_ANSWER_NO_OPTION,
            self::UNIQUE_ANSWER_IMAGE,
            self::READING_COMPREHENSION => $this->scoreUniqueAnswer($answers, $rows),
            self::MULTIPLE_ANSWER,
            self::GLOBAL_MULTIPLE_ANSWER,
            self::MULTIPLE_ANSWER_DROPDOWN => $this->scoreMultipleAnswer($answers, $rows),
            self::MULTIPLE_ANSWER_COMBINATION,
            self::MULTIPLE_ANSWER_DROPDOWN_COMBINATION => $this->scoreMultipleCombination($question, $answers, $rows),
            self::MULTIPLE_ANSWER_TRUE_FALSE => $this->scoreTrueFalseAnswer($question, $answers, $options, $rows),
            self::MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY => $this->scoreTrueFalseDegreeCertaintyAnswer($question, $answers, $options, $rows),
            self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE => $this->scoreTrueFalseCombination($question, $answers, $options, $rows),
            self::FILL_IN_BLANKS,
            self::FILL_IN_BLANKS_COMBINATION => $this->scoreFillBlanks($quiz, $question, $answers, $rows),
            self::MATCHING,
            self::MATCHING_DRAGGABLE => $this->scoreMatchingAnswer($answers, $rows),
            self::DRAGGABLE => $this->scoreDraggableAnswer($answers, $rows),
            self::MATCHING_COMBINATION,
            self::MATCHING_DRAGGABLE_COMBINATION => $this->scoreMatchingCombination($question, $answers, $rows),
            self::CALCULATED_ANSWER => $this->scoreCalculatedAnswer($question, $answers, $rows),
            self::HOT_SPOT => $this->scoreHotspotAnswer($answers, $rows, false, (float) $question->getPonderation()),
            self::HOT_SPOT_DELINEATION => $this->scoreHotspotDelineationAnswer($quiz, $question, $answers, $rows),
            self::HOT_SPOT_COMBINATION => $this->scoreHotspotAnswer($answers, $rows, true, (float) $question->getPonderation()),
            self::FREE_ANSWER,
            self::ORAL_EXPRESSION,
            self::UPLOAD_ANSWER,
            self::ANSWER_IN_OFFICE_DOC,
            self::ANNOTATION => $this->scoreManualAnswer($rows),
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
        if ($selectedAnswerId <= 0 || !isset($answers[$selectedAnswerId])) {
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
            if ($studentChoice <= 0) {
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
    private function scoreTrueFalseDegreeCertaintyAnswer(CQuizQuestion $question, array $answers, array $options, array $rows): float
    {
        [$trueScore, $falseScore, $doubtScore] = $this->getTrueFalseScores((string) $question->getExtra());
        $choices = $this->getSavedTrueFalseDegreeCertaintyChoices($rows);
        $score = 0.0;

        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $studentChoice = (int) ($choices[$answerId]['choice'] ?? 0);
            if ($studentChoice <= 0) {
                continue;
            }

            $studentDegreeChoice = (int) ($choices[$answerId]['degree'] ?? 0);
            $studentDegreeChoicePosition = $this->getTrueFalseOptionPosition($studentDegreeChoice, $options);
            $hasCertainty = $studentDegreeChoicePosition >= 3 && $studentDegreeChoicePosition < 9;

            if ($this->isTrueFalseChoiceCorrect($studentChoice, (int) $answer->getCorrect(), $options)) {
                $score += $hasCertainty ? $trueScore : $doubtScore;

                continue;
            }

            $score += $hasCertainty ? $falseScore : $doubtScore;
        }

        return $score;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, array{choice: int, degree: int}>
     */
    private function getSavedTrueFalseDegreeCertaintyChoices(array $rows): array
    {
        $choices = [];
        foreach ($rows as $row) {
            $parts = explode(':', (string) $row->getAnswer());
            $answerId = isset($parts[0]) ? (int) $parts[0] : 0;
            $optionId = isset($parts[1]) ? (int) $parts[1] : 0;
            $degreeId = isset($parts[2]) ? (int) $parts[2] : 0;
            if ($answerId > 0 && $optionId > 0) {
                $choices[$answerId] = [
                    'choice' => $optionId,
                    'degree' => $degreeId,
                ];
            }
        }

        return $choices;
    }

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
            if ($answerId > 0 && $optionId > 0) {
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
        if ($studentChoice <= 0 || $correctChoice <= 0) {
            return false;
        }

        if ($studentChoice === $correctChoice) {
            return true;
        }

        $studentPosition = $this->getTrueFalseOptionPosition($studentChoice, $options);
        $correctPosition = $this->getTrueFalseOptionPosition($correctChoice, $options);

        return $studentPosition > 0 && $studentPosition === $correctPosition;
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
     *     blank_separator_end: string,
     *     ...
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
        $studentAnswer = $this->trimFillBlankOption($studentAnswer);
        $normalizedStudentAnswer = $caseInsensitive ? mb_strtolower($studentAnswer) : $studentAnswer;

        if (str_contains($correctAnswer, '|') && !str_contains($correctAnswer, '||')) {
            $menuAnswers = array_map([$this, 'trimFillBlankOption'], explode('|', $correctAnswer));
            $firstAnswer = (string) ($menuAnswers[0] ?? '');
            $normalizedFirstAnswer = $caseInsensitive ? mb_strtolower($firstAnswer) : $firstAnswer;

            return $normalizedStudentAnswer === $normalizedFirstAnswer || $normalizedStudentAnswer === sha1($normalizedFirstAnswer);
        }

        if (str_contains($correctAnswer, '||')) {
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
    private function scoreDraggableAnswer(array $answers, array $rows): float
    {
        $positions = $this->getSavedDraggablePositions($rows);
        $score = 0.0;

        foreach ($answers as $answer) {
            $correctPosition = (int) ($answer->getCorrect() ?? 0);
            if ($correctPosition <= 0) {
                continue;
            }

            $answerId = (int) $answer->getIid();
            if (($positions[$answerId] ?? 0) === $correctPosition) {
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

        return $optionCount > 0 && $correctCount === $optionCount ? (float) $question->getPonderation() : 0.0;
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
            if (null === $position || $position <= 0) {
                continue;
            }

            $choices[(int) $position] = (int) $row->getAnswer();
        }

        return $choices;
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreHotspotAnswer(array $answers, array $rows, bool $combination, float $questionWeight): float
    {
        $points = $this->getSavedHotspotPoints($rows);
        if ([] === $points) {
            return 0.0;
        }

        $matchedAnswerIds = [];
        $score = 0.0;
        $scoringZoneCount = 0;

        foreach ($answers as $answer) {
            $answerId = (int) $answer->getIid();
            $hotspotType = (string) ($answer->getHotspotType() ?: 'square');
            if (!\in_array($hotspotType, ['square', 'circle', 'poly'], true)) {
                continue;
            }

            if ((float) $answer->getPonderation() > 0.0) {
                ++$scoringZoneCount;
            }

            foreach ($points as $point) {
                $pointAnswerId = (int) ($point['answerId'] ?? 0);
                if ($pointAnswerId > 0 && $pointAnswerId !== $answerId) {
                    continue;
                }

                if ($this->exerciseHotspotGeometryHelper->isPointInHotspot($point, $hotspotType, (string) $answer->getHotspotCoordinates())) {
                    $matchedAnswerIds[$answerId] = true;
                    if (!$combination) {
                        $score += (float) $answer->getPonderation();
                    }

                    break;
                }
            }
        }

        if (!$combination) {
            return $score;
        }

        return $scoringZoneCount > 0 && \count($matchedAnswerIds) >= $scoringZoneCount ? $questionWeight : 0.0;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, array{x: float, y: float, answerId?: int}>
     */
    private function getSavedHotspotPoints(array $rows): array
    {
        $points = [];
        foreach ($rows as $row) {
            foreach (explode('|', (string) $row->getAnswer()) as $coordinate) {
                $point = $this->exerciseHotspotGeometryHelper->decodeHotspotPoint($coordinate);
                if (null !== $point) {
                    $points[] = $point;
                }
            }
        }

        return $points;
    }

    /**
     * A calculated-answer question now scores per formula (each with its own tolerance), summing the
     * score of every formula the student's typed value falls within range of.
     *
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreCalculatedAnswer(CQuizQuestion $question, array $answers, array $rows): float
    {
        $row = $rows[0] ?? null;
        if (!$row instanceof TrackEAttempt) {
            return 0.0;
        }

        $teacherAnswer = reset($answers);
        if (!$teacherAnswer instanceof CQuizAnswer) {
            return 0.0;
        }

        $parsed = $this->parseCalculatedAnswer((string) $teacherAnswer->getAnswer());
        $studentValues = $this->parseCalculatedStudentAnswer((string) $row->getAnswer());
        $exeId = $row->getTrackEExercise()->getExeId();
        $questionIid = (int) ($question->getIid() ?? 0);

        $variableValues = [];
        foreach ($parsed['variables'] as $name => $variable) {
            $variableValues[$name] = $this->generateCalculatedVariableValue(
                (string) $variable['intervals'],
                (int) $variable['decimals'],
                $exeId,
                $questionIid,
                $name
            );
        }

        $score = 0.0;
        foreach ($parsed['formulas'] as $formula) {
            $studentValue = (string) ($studentValues[$formula['name']] ?? '');
            if ('' === $studentValue || !is_numeric($studentValue)) {
                continue;
            }

            [, $min, $max] = $this->evaluateCalculatedFormula(
                (string) $formula['formula'],
                $variableValues,
                (float) $formula['tolerance'],
                (string) $formula['toleranceType'],
                (int) $formula['decimals']
            );

            if ((float) $studentValue >= $min && (float) $studentValue <= $max) {
                $score += (float) $formula['score'];
            }
        }

        return $score;
    }

    /**
     * @return array<string, string>
     */
    private function parseCalculatedStudentAnswer(string $value): array
    {
        $values = [];
        foreach (explode(';', trim($value, ';')) as $pair) {
            if ('' === $pair) {
                continue;
            }

            $parts = explode(':', $pair, 2);
            if (2 !== \count($parts)) {
                continue;
            }

            $values[trim($parts[0])] = trim($parts[1]);
        }

        return $values;
    }

    /**
     * Parses the stored "wording@@@#name:intervals::decimals;=name:formula:tolerance:type:decimals:score;..."
     * encoding shared with the legacy exercise tool (public/main/exercise/calculated_answer.class.php).
     *
     * @return array{
     *     text: string,
     *     variables: array<string, array{name: string, intervals: string, decimals: int}>,
     *     formulas: array<string, array{name: string, formula: string, tolerance: float, toleranceType: string, decimals: int, score: float}>
     * }
     */
    private function parseCalculatedAnswer(string $answer): array
    {
        $parts = explode('@@@', $answer, 2);
        $text = (string) ($parts[0] ?? '');
        $encodedData = (string) ($parts[1] ?? '');

        $variables = [];
        $formulas = [];

        foreach (explode(';', trim($encodedData, ';')) as $item) {
            if ('' === $item) {
                continue;
            }

            $bits = explode(':', $item);
            if (str_starts_with($item, '#') && \count($bits) >= 4) {
                $name = ltrim($bits[0], '#');
                // Backward compatibility with the old "name:min:max:decimals" encoding (bits[2] holds max);
                // the current format leaves bits[2] empty and encodes the whole range in bits[1].
                $intervals = '' !== $bits[2] ? $bits[1].'-'.$bits[2] : $bits[1];
                $variables[$name] = [
                    'name' => $name,
                    'intervals' => $intervals,
                    'decimals' => (int) $bits[3],
                ];
            } elseif (str_starts_with($item, '=') && \count($bits) >= 6) {
                $name = ltrim($bits[0], '=');
                $formulas[$name] = [
                    'name' => $name,
                    'formula' => $bits[1],
                    'tolerance' => (float) $bits[2],
                    'toleranceType' => $bits[3],
                    'decimals' => (int) $bits[4],
                    'score' => (float) $bits[5],
                ];
            }
        }

        return ['text' => $text, 'variables' => $variables, 'formulas' => $formulas];
    }

    /**
     * Deterministic reimplementation matching ExerciseRuntimeProvider::generateCalculatedVariableValue()
     * so scoring uses the exact same generated values the student saw while answering.
     */
    private function generateCalculatedVariableValue(string $intervals, int $decimals, int $exeId, int $questionIid, string $variableName): float
    {
        if (is_numeric($intervals)) {
            return round((float) $intervals, $decimals);
        }

        $intervalList = explode('*', $intervals);
        $chosenInterval = $intervalList[$this->calculatedSeedIndex($exeId, $questionIid, $variableName, 0, \count($intervalList))];

        if (is_numeric($chosenInterval)) {
            return round((float) $chosenInterval, $decimals);
        }

        if (str_contains($chosenInterval, '|')) {
            $parts = explode('|', $chosenInterval);
            $allNumeric = true;
            foreach ($parts as $part) {
                if (!is_numeric(trim($part))) {
                    $allNumeric = false;

                    break;
                }
            }

            if ($allNumeric) {
                $index = $this->calculatedSeedIndex($exeId, $questionIid, $variableName, 1, \count($parts));

                return round((float) $parts[$index], $decimals);
            }

            [$chosenInterval, $step] = explode('|', $chosenInterval, 2);
            $step = (float) $step;
            [$minimum, $maximum] = $this->parseCalculatedInterval($chosenInterval);
            if ($minimum > $maximum) {
                [$minimum, $maximum] = [$maximum, $minimum];
            }

            if ($step <= 0) {
                return $minimum;
            }

            $factor = 10 ** $decimals;
            $minimumScaled = (int) round($minimum * $factor);
            $maximumScaled = (int) round($maximum * $factor);
            $stepScaled = max(1, (int) round($step * $factor));
            $steps = (int) floor(($maximumScaled - $minimumScaled) / $stepScaled);
            $chosenStep = $this->calculatedSeedIndex($exeId, $questionIid, $variableName, 2, $steps + 1);

            return round(($minimumScaled + $chosenStep * $stepScaled) / $factor, $decimals);
        }

        [$minimum, $maximum] = $this->parseCalculatedInterval($chosenInterval);
        if ($minimum > $maximum) {
            [$minimum, $maximum] = [$maximum, $minimum];
        }

        if (0 === $decimals) {
            $offset = $this->calculatedSeedIndex($exeId, $questionIid, $variableName, 3, ((int) $maximum - (int) $minimum) + 1);

            return (float) ((int) $minimum + $offset);
        }

        $fraction = $this->calculatedSeedFraction($exeId, $questionIid, $variableName, 4);

        return round($minimum + ($fraction * ($maximum - $minimum)), $decimals);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function parseCalculatedInterval(string $interval): array
    {
        $parts = preg_split('/(?<!^)-(?!$)/', trim($interval), 2);
        if (2 !== \count($parts)) {
            return [0.0, 0.0];
        }

        return [(float) trim($parts[0]), (float) trim($parts[1])];
    }

    private function calculatedSeedFraction(int $exeId, int $questionIid, string $variableName, int $salt): float
    {
        // md5, not crc32: crc32 barely mixes a short trailing difference (the variable name) into its
        // high-order bits, so short names sharing a common exeId:questionIid prefix (e.g. #a, #b, #c, #d)
        // produced near-identical fractions and collided into the same drawn value almost every time.
        return hexdec(substr(md5(\sprintf('%d:%d:%s:%d', $exeId, $questionIid, $variableName, $salt)), 0, 8)) / 4294967295;
    }

    private function calculatedSeedIndex(int $exeId, int $questionIid, string $variableName, int $salt, int $count): int
    {
        if ($count <= 1) {
            return 0;
        }

        return (int) floor($this->calculatedSeedFraction($exeId, $questionIid, $variableName, $salt) * $count) % $count;
    }

    private function formatCalculatedValue(float $value, int $decimals): string
    {
        if (0 === $decimals) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, $decimals, '.', ''), '0'), '.');
    }

    /**
     * @param array<string, float> $variableValues
     *
     * @return array{0: float, 1: float, 2: float} [result, min, max]
     */
    private function evaluateCalculatedFormula(string $formula, array $variableValues, float $tolerance, string $toleranceType, int $decimals): array
    {
        $expression = ' '.$formula.' ';
        foreach ($variableValues as $name => $value) {
            $expression = (string) preg_replace(
                '/\b'.preg_quote($name, '/').'\b/',
                ' '.$this->formatCalculatedValue($value, 10).' ',
                $expression
            );
        }

        // EvalMath's log() maps straight to PHP's log() (natural log), not base 10 — match the
        // legacy exercise tool (public/main/exercise/calculated_answer.class.php) by rewriting
        // log(x) to the base-10 ratio before evaluating.
        $expression = (string) preg_replace('/log\(([^)]+)\)/', '(ln($1)/ln(10))', $expression);

        $math = new EvalMath();
        $result = $math->evaluate($expression);
        $result = false === $result ? 0.0 : (float) $result;

        $min = $result;
        $max = $result;
        if ($tolerance > 0) {
            $delta = 'percent' === $toleranceType ? abs($result) * $tolerance / 100 : $tolerance;
            $min = $result - $delta;
            $max = $result + $delta;
        }

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [round($result, $decimals), round($min, $decimals), round($max, $decimals)];
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
    public function getQuestionWeight(CQuizQuestion $question, array $answers): float
    {
        $type = (int) $question->getType();
        if (\in_array($type, [self::MEDIA_QUESTION, self::PAGE_BREAK], true)) {
            return 0.0;
        }

        if (self::CALCULATED_ANSWER === $type) {
            return (float) $question->getPonderation();
        }

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
    private function getSavedDraggablePositions(array $rows): array
    {
        $positions = [];
        foreach ($rows as $row) {
            $answerId = (int) $row->getPosition();
            $selectedPosition = (int) $row->getAnswer();
            if ($answerId > 0 && $selectedPosition > 0) {
                $positions[$answerId] = $selectedPosition;
            }
        }

        return $positions;
    }

    public function requiresManualCorrection(CQuizQuestion $question): bool
    {
        return \in_array((int) $question->getType(), [self::FREE_ANSWER, self::ORAL_EXPRESSION, self::UPLOAD_ANSWER, self::ANSWER_IN_OFFICE_DOC, self::ANNOTATION], true);
    }

    private function getSavedAnswerIds(array $rows): array
    {
        $answerIds = [];
        foreach ($rows as $row) {
            $answerId = (int) $row->getAnswer();
            if ($answerId > 0 && !\in_array($answerId, $answerIds, true)) {
                $answerIds[] = $answerId;
            }
        }

        return $answerIds;
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     */
    public function updateQuestionAttemptRows(CQuizQuestion $question, array $rows, float $score): void
    {
        foreach ($rows as $row) {
            $row->setMarks($score);
            $row->setTms(new DateTime());
        }

        if ([] === $rows && !$this->requiresManualCorrection($question)) {
            return;
        }
    }

    /**
     * @param array<int, CQuizAnswer>   $answers
     * @param array<int, TrackEAttempt> $rows
     */
    private function scoreHotspotDelineationAnswer(CQuiz $quiz, CQuizQuestion $question, array $answers, array $rows): float
    {
        $studentPolygon = $this->getSavedDelineationPolygon($rows);
        if (\count($studentPolygon) < 3) {
            return 0.0;
        }

        $teacherDelineation = null;
        $organsAtRisk = [];
        foreach ($answers as $answer) {
            $hotspotType = (string) $answer->getHotspotType();
            if ('delineation' === $hotspotType && null === $teacherDelineation) {
                $teacherDelineation = $answer;

                continue;
            }

            if ('oar' === $hotspotType) {
                $organsAtRisk[] = $answer;
            }
        }

        if (!$teacherDelineation instanceof CQuizAnswer) {
            return 0.0;
        }

        $teacherPolygon = $this->exerciseHotspotGeometryHelper->parseDelineationPolygon((string) $teacherDelineation->getHotspotCoordinates());
        if (\count($teacherPolygon) < 3) {
            return 0.0;
        }

        $metrics = $this->exerciseHotspotGeometryHelper->getDelineationOverlapMetrics($teacherPolygon, $studentPolygon);
        $thresholds = $this->getDelineationThresholds($quiz, $question, (int) $teacherDelineation->getPosition());

        if ($metrics['overlap'] < $thresholds['minOverlap']) {
            return 0.0;
        }

        if ($metrics['excess'] > $thresholds['maxExcess']) {
            return 0.0;
        }

        if ($metrics['missing'] > $thresholds['maxMissing']) {
            return 0.0;
        }

        foreach ($organsAtRisk as $organAtRisk) {
            $organPolygon = $this->exerciseHotspotGeometryHelper->parseDelineationPolygon((string) $organAtRisk->getHotspotCoordinates());
            if (\count($organPolygon) >= 3 && $this->exerciseHotspotGeometryHelper->polygonsOverlap($studentPolygon, $organPolygon)) {
                return 0.0;
            }
        }

        $score = (float) $teacherDelineation->getPonderation();

        return $score > 0.0 ? $score : (float) $question->getPonderation();
    }

    /**
     * @return array{minOverlap: float, maxExcess: float, maxMissing: float}
     */
    private function getDelineationThresholds(CQuiz $quiz, CQuizQuestion $question, int $position): array
    {
        $relation = $this->entityManager->getRepository(CQuizRelQuestion::class)->findOneBy([
            'quiz' => $quiz,
            'question' => $question,
        ]);

        if (!$relation instanceof CQuizRelQuestion || '' === (string) $relation->getDestination()) {
            return ['minOverlap' => 1.0, 'maxExcess' => 100.0, 'maxMissing' => 100.0];
        }

        $destination = json_decode((string) $relation->getDestination(), true);
        if (!\is_array($destination)) {
            return ['minOverlap' => 1.0, 'maxExcess' => 100.0, 'maxMissing' => 100.0];
        }

        $thresholds = \is_array($destination['thresholds'] ?? null) ? $destination['thresholds'] : [];
        $positionThresholds = \is_array($thresholds[(string) $position] ?? null) ? $thresholds[(string) $position] : [];

        return [
            'minOverlap' => $this->normalizePercentage($positionThresholds['minOverlap'] ?? 1.0, 1.0),
            'maxExcess' => $this->normalizePercentage($positionThresholds['maxExcess'] ?? 100.0, 100.0),
            'maxMissing' => $this->normalizePercentage($positionThresholds['maxMissing'] ?? 100.0, 100.0),
        ];
    }

    /**
     * @param array<int, TrackEAttempt> $rows
     *
     * @return array<int, array{x: float, y: float}>
     */
    private function getSavedDelineationPolygon(array $rows): array
    {
        $row = $rows[0] ?? null;
        if (!$row instanceof TrackEAttempt) {
            return [];
        }

        return $this->exerciseHotspotGeometryHelper->parseDelineationPolygon((string) $row->getAnswer());
    }

    private function normalizePercentage(mixed $value, float $default): float
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return min(100.0, max(0.0, (float) $value));
    }
}
