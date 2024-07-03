<?php

/* For licensing terms, see /license.txt */

class HookPortfolioCommentEdited extends HookEvent implements HookPortfolioCommentEditedEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookPortfolioCommentEdited');
    }

    public function notifyCommentEdited()
    {
        /** @var HookPortfolioCommentEditedObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookCommentEdited($this);
        }
    }
}
