<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookLearningPathItemViewedObserverInterface.
 */
interface HookLearningPathItemViewedObserverInterface extends HookObserverInterface
{
    /**
     * @param \HookLearningPathItemViewedEventInterface $event
     *
     * @return mixed
     */
    public function hookLearningPathItemViewed(HookLearningPathItemViewedEventInterface $event);
}
