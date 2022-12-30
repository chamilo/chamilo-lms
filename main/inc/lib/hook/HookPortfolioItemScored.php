<?php

/* For licensing terms, see /license.txt */

class HookPortfolioItemScored extends HookEvent implements HookPortfolioItemScoredEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookPortfolioItemScored');
    }

    public function notifyItemScored(): void
    {
        /** @var HookPortfolioItemScoredObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookItemScored($this);
        }
    }
}
