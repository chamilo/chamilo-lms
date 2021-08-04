<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookLearningPathEndObserverInterface.
 */
interface HookLearningPathEndObserverInterface extends HookObserverInterface
{
    public function notifyLearningPathEnd(HookLearningPathEndEventInterface $event);
}
