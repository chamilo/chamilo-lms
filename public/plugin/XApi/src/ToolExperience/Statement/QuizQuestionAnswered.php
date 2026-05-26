<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\Quiz as QuizActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\QuizQuestion as QuizQuestionActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Answered as AnsweredVerb;

/**
 * Class QuizQuestionAnswered.
 */
class QuizQuestionAnswered extends BaseStatement
{
    private TrackEAttempt $attempt;
    private CQuizQuestion $question;
    private CQuiz $quiz;

    public function __construct(TrackEAttempt $attempt, CQuizQuestion $question, CQuiz $quiz)
    {
        $this->attempt = $attempt;
        $this->question = $question;
        $this->quiz = $quiz;
    }

    public function generate(): array
    {
        $user = $this->attempt->getUser();

        $userActor = new UserActor($user);
        $answeredVerb = new AnsweredVerb();
        $questionActivity = new QuizQuestionActivity($this->question);
        $quizActivity = new QuizActivity($this->quiz);

        $rawResult = (float) $this->attempt->getMarks();
        $maxResult = (float) $this->question->getPonderation();
        $scaledResult = $maxResult > 0 ? ($rawResult / $maxResult) : 0.0;

        $result = $this->buildResult(
            $this->buildScore(
                $scaledResult,
                $rawResult,
                null,
                $maxResult > 0 ? $maxResult : null
            ),
            $rawResult > 0,
            true
        );

        $context = $this->mergeGroupingActivity(
            $this->generateContext(),
            $quizActivity->generate()
        );

        return [
            'id' => $this->generateStatementId('exercise-question'),
            'actor' => $userActor->generate(),
            'verb' => $answeredVerb->generate(),
            'object' => $questionActivity->generate(),
            'result' => $result,
            'timestamp' => $this->normalizeTimestamp($this->attempt->getTms()),
            'context' => $context,
        ];
    }

    private function normalizeTimestamp($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        $stringValue = trim((string) $value);

        if ('' === $stringValue) {
            return gmdate(DATE_ATOM);
        }

        $timestamp = strtotime($stringValue);

        return false !== $timestamp ? gmdate(DATE_ATOM, $timestamp) : gmdate(DATE_ATOM);
    }
}
