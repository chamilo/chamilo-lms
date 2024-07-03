<?php

/* For licensing terms, see /license.txt */

class HookPortfolioItemViewed extends HookEvent implements HookPortfolioItemViewedEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookPortfolioItemViewed');
    }

    public function notifyItemViewed(): void
    {
        /** @var HookPortfolioItemViewedObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookItemViewed($this);
        }
    }
}
