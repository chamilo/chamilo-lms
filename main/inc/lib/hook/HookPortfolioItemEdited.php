<?php

/* For licensing terms, see /license.txt */

class HookPortfolioItemEdited extends HookEvent implements HookPortfolioItemEditedEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookPortfolioItemEdited');
    }

    public function notifyItemEdited()
    {
        /** @var HookPortfolioItemEditedObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookItemEdited($this);
        }
    }
}
