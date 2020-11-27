<?php

/* For licensing terms, see /license.txt */

/**
 * Class HookLearningPathEnd.
 */
class HookLearningPathEnd extends HookEvent implements HookLearningPathEndEventInterface
{
    /**
     * HookLearningPathEnd constructor.
     *
     * @throws \Exception
     */
    protected function __construct()
    {
        parent::__construct('HookLearningPathEndEvent');
    }

    /**
     * {@inheritdoc}
     */
    public function hookLearningPathEnd()
    {
        /** @var \HookLearningPathEndObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->notifyLearningPathEnd($this);
        }
    }
}
