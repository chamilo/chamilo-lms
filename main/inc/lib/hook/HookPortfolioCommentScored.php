<?php

/* For licensing terms, see /license.txt */

class HookPortfolioCommentScored extends HookEvent implements HookPortfolioCommentScoredEventInterface
{
    protected function __construct()
    {
        parent::__construct('HookPortfolioCommentScored');
    }

    public function notifyCommentScored()
    {
        /** @var HookPortfolioCommentScoredObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookCommentScored($this);
        }
    }
}
