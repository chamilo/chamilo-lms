<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookQuizQuestionAnsweredObserverInterface.
 */
interface HookQuizQuestionAnsweredObserverInterface extends HookObserverInterface
{
    /**
     * @param \HookQuizQuestionAnsweredEventInterface $event
     *
     * @return mixed
     */
    public function hookQuizQuestionAnswered(HookQuizQuestionAnsweredEventInterface $event);
}
