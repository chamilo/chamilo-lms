<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookQuizEndEventInterface.
 */
interface HookQuizEndEventInterface extends HookEventInterface
{
    /**
     * @return void
     */
    public function notifyQuizEnd();
}
