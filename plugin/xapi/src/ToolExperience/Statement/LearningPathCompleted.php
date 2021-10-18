<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CourseBundle\Entity\CLp as CLpEntity;
use Chamilo\CourseBundle\Entity\CLpView as CLpViewEntity;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\LearningPath as LearningPathActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Completed;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\Score;
use Xabbuh\XApi\Model\Statement;

/**
 * Class LearningPathCompleted.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Statement
 */
class LearningPathCompleted extends BaseStatement
{
    /**
     * @var \Chamilo\CourseBundle\Entity\CLpView
     */
    private $lpView;
    /**
     * @var \Chamilo\CourseBundle\Entity\CLp
     */
    private $lp;

    public function __construct(CLpViewEntity $lpView, CLpEntity $lp)
    {
        $this->lpView = $lpView;
        $this->lp = $lp;
    }

    public function generate(): Statement
    {
        $user = api_get_user_entity($this->lpView->getUserId());
        $userActor = new UserActor($user);
        $completedVerb = new Completed();
        $lpActivity = new LearningPathActivity($this->lp);

        return new Statement(
            $this->generateStatementId('learning-path'),
            $userActor->generate(),
            $completedVerb->generate(),
            $lpActivity->generate(),
            new Result(
                new Score(1, 100, 0, 100),
                null,
                true
            ),
            null,
            api_get_utc_datetime(null, false, true),
            null,
            $this->generateContext()
        );
    }
}
