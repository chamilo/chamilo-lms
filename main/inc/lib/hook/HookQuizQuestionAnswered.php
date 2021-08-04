<?php

/* For licensing terms, see /license.txt */

/**
 * Class HookQuizQuestionAnswered.
 */
class HookQuizQuestionAnswered extends HookEvent implements HookQuizQuestionAnsweredEventInterface
{
    /**
     * HookQuizQuestionAnswered constructor.
     *
     * @throws \Exception
     */
    protected function __construct()
    {
        parent::__construct('HookQuestionAnswered');
    }

    /**
     * {@inheritdoc}
     */
    public function notifyQuizQuestionAnswered()
    {
        /** @var \HookQuizQuestionAnsweredObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookQuizQuestionAnswered($this);
        }
    }
}
