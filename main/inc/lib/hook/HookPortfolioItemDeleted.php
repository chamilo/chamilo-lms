<?php

/* For licensing terms, see /license.txt */

class HookPortfolioItemDeleted extends HookEvent implements HookPortfolioItemDeletedEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookPortfolioItemDeleted');
    }

    public function notifyItemDeleted()
    {
        /** @var HookPortfolioItemDeletedHookObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookItemDeleted($this);
        }
    }
}
