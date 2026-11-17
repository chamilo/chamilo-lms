<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CourseBundle\Entity\CQuiz;

/**
 * Whether a learner may see the score of their attempts, according to the results mode of the
 * exercise.
 *
 * The exercise page and the exercise list both show a score: they have to answer the same way,
 * or the list gives away what the exam mode hides on the exercise page.
 */
readonly class ExerciseScoreVisibilityHelper
{
    private const int RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS = 0;
    private const int RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS = 1;
    private const int RESULT_DISABLE_SHOW_SCORE_ONLY = 2;
    private const int RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES = 3;
    private const int RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT = 4;
    private const int RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK = 5;
    private const int RESULT_DISABLE_RANKING = 6;
    private const int RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER = 7;
    private const int RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING = 8;
    private const int RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK = 10;

    /**
     * @param int $attemptCount the learner's own finished attempts at this exercise
     */
    public function isScoreVisible(CQuiz $quiz, int $attemptCount): bool
    {
        $resultMode = (int) $quiz->getResultsDisabled();
        if (self::RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS === $resultMode) {
            return false;
        }

        if (
            self::RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK === $resultMode
            && !$this->canShowLastAttemptAnswers($quiz, $attemptCount)
        ) {
            return false;
        }

        return \in_array(
            $resultMode,
            [
                self::RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                self::RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                self::RESULT_DISABLE_SHOW_SCORE_ONLY,
                self::RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                self::RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
                self::RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
                self::RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
                self::RESULT_DISABLE_RANKING,
                self::RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            ],
            true
        );
    }

    /**
     * @param int $attemptCount the learner's own finished attempts at this exercise
     */
    public function canShowLastAttemptAnswers(CQuiz $quiz, int $attemptCount): bool
    {
        $maxAttempt = (int) $quiz->getMaxAttempt();

        return $maxAttempt <= 0 || $attemptCount >= $maxAttempt;
    }
}
