<?php

/* For licensing terms, see /license.txt */

/**
 * Class HookPortfolioItemAdded.
 */
class HookPortfolioItemAdded extends HookEvent implements HookPortfolioItemAddedEventInterface
{
    /**
     * HookPortfolioItemAdded constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct('HookPortfolioItemAdded');
    }

    /**
     * {@inheritDoc}
     */
    public function notifyItemAdded()
    {
        /** @var \HookPortfolioItemAddedObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookItemAdded($this);
        }
    }
}
