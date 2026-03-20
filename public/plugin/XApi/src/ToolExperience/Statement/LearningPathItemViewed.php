<?php

declare(strict_types=1);

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

/**
 * Class LearningPathItemViewed.
 */
class LearningPathItemViewed extends BaseStatement
{
    private CLpItemView $lpItemView;
    private CLpItem $lpItem;
    private CLpView $lpView;

    public function __construct(CLpItemView $lpItemView, CLpItem $lpItem, CLpView $lpView)
    {
        $this->lpItemView = $lpItemView;
        $this->lpItem = $lpItem;
        $this->lpView = $lpView;
    }

    public function generate(): array
    {
        $user = $this->lpView->getUser();
        $lp = $this->lpView->getLp();

        $userActor = new UserActor($user);
        $viewedVerb = new ViewedVerb();
        $lpItemActivity = new LearningPathItemActivity($this->lpItem);

        $context = $this->generateContext();

        if ($lp instanceof CLp) {
            $lpActivity = new LearningPath($lp);
            $context = $this->mergeGroupingActivity($context, $lpActivity->generate());
        }

        $status = (string) $this->lpItemView->getStatus();
        $totalTime = (int) $this->lpItemView->getTotalTime();

        return [
            'id' => $this->generateStatementId('learning-path-item'),
            'actor' => $userActor->generate(),
            'verb' => $viewedVerb->generate(),
            'object' => $lpItemActivity->generate(),
            'result' => $this->buildResult(
                [],
                null,
                'completed' === strtolower(trim($status)),
                null,
                $totalTime > 0 ? 'PT'.$totalTime.'S' : null
            ),
            'timestamp' => api_get_utc_datetime(null, false, true)->format(DATE_ATOM),
            'context' => $context,
        ];
    }
}
