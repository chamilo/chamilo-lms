<?php

/* For licensing terms, see /license.txt */

/**
 * Class HookLearningPathItemViewed.
 */
class HookLearningPathItemViewed extends HookEvent implements HookLearningPathItemViewedEventInterface
{
    /**
     * HookLearningPathItemViewed constructor.
     *
     * @throws \Exception
     */
    protected function __construct()
    {
        parent::__construct('HookLearningPathItemViewed');
    }

    /**
     * {@inheritdoc}
     */
    public function notifyLearningPathItemViewed()
    {
        /** @var \HookLearningPathItemViewedObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookLearningPathItemViewed($this);
        }
    }
}
