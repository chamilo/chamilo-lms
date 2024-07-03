<?php

/* For licensing terms, see /license.txt */

class HookPortfolioItemVisibility extends HookEvent implements HookPortfolioItemVisibilityEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookPortfolioItemVisibility');
    }

    /**
     * {@inheritDoc}
     */
    public function notifyItemVisibility()
    {
        /** @var HookPortfolioItemVisibilityObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookItemVisibility($this);
        }
    }
}
