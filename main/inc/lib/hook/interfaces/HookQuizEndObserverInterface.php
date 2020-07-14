<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookQuizEndObserverInterface.
 */
interface HookQuizEndObserverInterface
{
    /**
     * @param HookQuizEndEventInterface $hookvent
     *
     * @return mixed
     */
    public function hookQuizEnd(HookQuizEndEventInterface $hookvent);
}
