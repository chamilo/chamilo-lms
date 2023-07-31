<?php

/* For licensing terms, see /license.txt */

class HookLearningPathCreated extends HookEvent implements HookLearningPathCreatedEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookLearningPathCreated');
    }

    public function notifyCreated()
    {
        /** @var HookLearningPathCreatedObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookCreated($this);
        }
    }
}
