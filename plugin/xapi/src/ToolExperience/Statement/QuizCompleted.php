<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\Quiz as QuizActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Completed as CompletedVerb;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\Score;
use Xabbuh\XApi\Model\Statement;

/**
 * Class QuizCompleted.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Statement
 */
class QuizCompleted extends BaseStatement
{
    /**
     * @var \Chamilo\CoreBundle\Entity\TrackEExercises
     */
    private $exe;
    /**
     * @var \Chamilo\CourseBundle\Entity\CQuiz
     */
    private $quiz;

    public function __construct(TrackEExercises $exe, CQuiz $quiz)
    {
        $this->exe = $exe;
        $this->quiz = $quiz;
    }

    public function generate(): Statement
    {
        $user = api_get_user_entity($this->exe->getExeUserId());

        $userActor = new UserActor($user);
        $completedVerb = new CompletedVerb();
        $quizActivity = new QuizActivity($this->quiz);

        $rawResult = $this->exe->getExeResult();
        $maxResult = $this->exe->getExeWeighting();
        $scaledResult = $rawResult / $maxResult;

        $duration = $this->exe->getExeDuration();

        return new Statement(
            $this->generateStatementId('exercise'),
            $userActor->generate(),
            $completedVerb->generate(),
            $quizActivity->generate(),
            new Result(
                new Score($scaledResult, $rawResult, 0, $maxResult),
                null,
                true,
                null,
                $duration ? "PT{$duration}S" : null
            ),
            null,
            $this->exe->getExeDate(),
            null,
            $this->generateContext()
        );
    }
}
