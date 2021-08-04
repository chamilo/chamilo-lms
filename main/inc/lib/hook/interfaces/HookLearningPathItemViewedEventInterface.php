<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookLearningPathItemViewedEventInterface.
 */
interface HookLearningPathItemViewedEventInterface extends HookEventInterface
{
    /**
     * @return mixed
     */
    public function notifyLearningPathItemViewed();
}
