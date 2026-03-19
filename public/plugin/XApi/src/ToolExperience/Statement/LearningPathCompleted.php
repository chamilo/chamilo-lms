<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\LearningPath as LearningPathActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Completed;

/**
 * Class LearningPathCompleted.
 */
class LearningPathCompleted extends BaseStatement
{
    private CLpView $lpView;
    private CLp $lp;

    public function __construct(CLpView $lpView, CLp $lp)
    {
        $this->lpView = $lpView;
        $this->lp = $lp;
    }

    public function generate(): array
    {
        $user = api_get_user_entity($this->lpView->getUserId());

        $userActor = new UserActor($user);
        $completedVerb = new Completed();
        $lpActivity = new LearningPathActivity($this->lp);

        return [
            'id' => $this->generateStatementId('learning-path'),
            'actor' => $userActor->generate(),
            'verb' => $completedVerb->generate(),
            'object' => $lpActivity->generate(),
            'result' => $this->buildResult(
                $this->buildScore(1.0, 100.0, 0.0, 100.0),
                null,
                true
            ),
            'timestamp' => api_get_utc_datetime(null, false, true)->format(DATE_ATOM),
            'context' => $this->generateContext(),
        ];
    }
}
