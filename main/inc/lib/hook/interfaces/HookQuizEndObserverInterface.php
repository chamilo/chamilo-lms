<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookQuizEndObserverInterface.
 */
interface HookQuizEndObserverInterface
{
    /**
     * @param \HookQuizEndEventInterface $hookEvent
     *
     * @return mixed
     */
    public function hookQuizEnd(HookQuizEndEventInterface $hookEvent);
}
