<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookQuizEndObserverInterface.
 */
interface HookQuizEndObserverInterface
{
    /**
     * @return mixed
     */
    public function hookQuizEnd(HookQuizEndEventInterface $hookvent);
}
