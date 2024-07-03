<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\LearningPath;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\LearningPathItem as LearningPathItemActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Actor\User as UserActor;
use Chamilo\PluginBundle\XApi\ToolExperience\Verb\Viewed as ViewedVerb;
use Database;
use Xabbuh\XApi\Model\Result;
use Xabbuh\XApi\Model\Statement;

/**
 * Class LearningPathItemViewed.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Statement
 */
class LearningPathItemViewed extends BaseStatement
{
    /**
     * @var \Chamilo\CourseBundle\Entity\CLpItemView
     */
    private $lpItemView;
    /**
     * @var \Chamilo\CourseBundle\Entity\CLpItem
     */
    private $lpItem;
    /**
     * @var \Chamilo\CourseBundle\Entity\CLpView
     */
    private $lpView;

    public function __construct(CLpItemView $lpItemView, CLpItem $lpItem, CLpView $lpView)
    {
        $this->lpItemView = $lpItemView;
        $this->lpItem = $lpItem;
        $this->lpView = $lpView;
    }

    public function generate(): Statement
    {
        $user = api_get_user_entity($this->lpView->getUserId());
        $lp = Database::getManager()->find(CLp::class, $this->lpView->getLpId());

        $userActor = new UserActor($user);
        $viewedVerb = new ViewedVerb();
        $lpItemActivity = new LearningPathItemActivity($this->lpItem);
        $lpActivity = new LearningPath($lp);

        $context = $this->generateContext();
        $contextActivities = $context
            ->getContextActivities()
            ->withAddedGroupingActivity($lpActivity->generate());

        return new Statement(
            $this->generateStatementId('learning-path-item'),
            $userActor->generate(),
            $viewedVerb->generate(),
            $lpItemActivity->generate(),
            new Result(
                null,
                null,
                'completed' === $this->lpItemView->getStatus(),
                null,
                'PT'.$this->lpItemView->getTotalTime().'S'
            ),
            null,
            api_get_utc_datetime(null, false, true),
            null,
            $context->withContextActivities($contextActivities)
        );
    }
}
