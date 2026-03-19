<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\Quiz as QuizActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Completed as CompletedVerb;

/**
 * Class QuizCompleted.
 */
class QuizCompleted extends BaseStatement
{
    private TrackEExercises $exe;
    private CQuiz $quiz;

    public function __construct(TrackEExercises $exe, CQuiz $quiz)
    {
        $this->exe = $exe;
        $this->quiz = $quiz;
    }

    public function generate(): array
    {
        $user = api_get_user_entity($this->exe->getExeUserId());

        $userActor = new UserActor($user);
        $completedVerb = new CompletedVerb();
        $quizActivity = new QuizActivity($this->quiz);

        $rawResult = (float) $this->exe->getExeResult();
        $maxResult = (float) $this->exe->getExeWeighting();
        $scaledResult = $maxResult > 0 ? ($rawResult / $maxResult) : 0.0;

        $duration = $this->exe->getExeDuration();
        $result = $this->buildResult(
            $this->buildScore($scaledResult, $rawResult, 0.0, $maxResult > 0 ? $maxResult : null),
            null,
            true,
            null,
            $duration ? "PT{$duration}S" : null
        );

        return [
            'id' => $this->generateStatementId('exercise'),
            'actor' => $userActor->generate(),
            'verb' => $completedVerb->generate(),
            'object' => $quizActivity->generate(),
            'result' => $result,
            'timestamp' => $this->normalizeTimestamp($this->exe->getExeDate()),
            'context' => $this->generateContext(),
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
