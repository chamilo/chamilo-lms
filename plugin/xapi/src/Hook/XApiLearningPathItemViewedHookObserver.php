<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\ToolExperience\Statement\LearningPathItemViewed;

/**
 * Class XApiLearningPathItemViewedHookObserver.
 */
class XApiLearningPathItemViewedHookObserver extends XApiActivityHookObserver implements HookLearningPathItemViewedObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function hookLearningPathItemViewed(HookLearningPathItemViewedEventInterface $event)
    {
        $data = $event->getEventData();
        $em = Database::getManager();

        $lpItemView = $em->find('ChamiloCourseBundle:CLpItemView', $data['item_view_id']);
        $lpItem = $em->find('ChamiloCourseBundle:CLpItem', $lpItemView->getLpItemId());

        if ('quiz' == $lpItem->getItemType()) {
            return null;
        }

        $lpView = $em->find('ChamiloCourseBundle:CLpView', $lpItemView->getLpViewId());

        $lpItemViewed = new LearningPathItemViewed($lpItemView, $lpItem, $lpView);

        $statement = $lpItemViewed->generate();

        $this->saveSharedStatement($statement);
    }
}
