<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\ToolExperience\Statement\LearningPathCompleted;

/**
 * Class XApiLearningPathEndHookObserver.
 */
class XApiLearningPathEndHookObserver extends XApiActivityHookObserver implements HookLearningPathEndObserverInterface
{
    public function notifyLearningPathEnd(HookLearningPathEndEventInterface $event)
    {
        $data = $event->getEventData();
        $em = Database::getManager();

        $lpView = $em->find('ChamiloCourseBundle:CLpView', $data['lp_view_id']);
        $lp = $em->find('ChamiloCourseBundle:CLp', $lpView->getLpId());

        $learningPathEnded = new LearningPathCompleted($lpView, $lp);

        $statement = $learningPathEnded->generate();

        $this->saveSharedStatement($statement);
    }
}
