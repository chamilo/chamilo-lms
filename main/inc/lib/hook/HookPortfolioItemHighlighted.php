<?php

/* For licensing terms, see /license.txt */

class HookPortfolioItemHighlighted extends HookEvent implements HookPortfolioItemHighlightedEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookPortfolioItemHighlighted');
    }

    public function notifyItemHighlighted()
    {
        /** @var HookPortfolioItemHighlightedObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookItemHighlighted($this);
        }
    }
}
