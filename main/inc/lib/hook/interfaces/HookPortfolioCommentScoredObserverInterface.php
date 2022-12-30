<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioCommentScoredObserverInterface extends HookObserverInterface
{
    public function hookCommentScored(HookPortfolioCommentScoredEventInterface $hookEvent);
}
