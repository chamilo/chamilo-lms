<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\Quiz as QuizActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\QuizQuestion as QuizQuestionActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Answered as AnsweredVerb;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\Score;
use Xabbuh\XApi\Model\Statement;

/**
 * Class QuizQuestionAnswered.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Statement
 */
class QuizQuestionAnswered extends BaseStatement
{
    /**
     * @var \Chamilo\CoreBundle\Entity\TrackEAttempt
     */
    private $attempt;
    /**
     * @var \Chamilo\CourseBundle\Entity\CQuizQuestion
     */
    private $question;
    /**
     * @var \Chamilo\CourseBundle\Entity\CQuiz
     */
    private $quiz;

    public function __construct(TrackEAttempt $attempt, CQuizQuestion $question, CQuiz $quiz)
    {
        $this->attempt = $attempt;
        $this->question = $question;
        $this->quiz = $quiz;
    }

    public function generate(): Statement
    {
        $user = api_get_user_entity($this->attempt->getUserId());

        $userActor = new UserActor($user);
        $answeredVerb = new AnsweredVerb();
        $questionActivity = new QuizQuestionActivity($this->question);
        $quizActivity = new QuizActivity($this->quiz);

        $rawResult = $this->attempt->getMarks();
        $maxResult = $this->question->getPonderation();
        $scaledResult = $maxResult ? ($rawResult / $maxResult) : 0;

        $context = $this->generateContext();
        $contextActivities = $context
            ->getContextActivities()
            ->withAddedGroupingActivity($quizActivity->generate());

        return new Statement(
            $this->generateStatementId('exercise-question'),
            $userActor->generate(),
            $answeredVerb->generate(),
            $questionActivity->generate(),
            new Result(
                new Score($scaledResult, $rawResult, null, $maxResult),
                $rawResult > 0,
                true
            ),
            null,
            $this->attempt->getTms(),
            null,
            $context->withContextActivities($contextActivities)
        );
    }
}
