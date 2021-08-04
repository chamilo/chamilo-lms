<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookQuizQuestionAnsweredEventInterface.
 */
interface HookQuizQuestionAnsweredEventInterface extends HookEventInterface
{
    /**
     * @return mixed
     */
    public function notifyQuizQuestionAnswered();
}
