<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioItemScoredObserverInterface extends HookObserverInterface
{
    public function hookItemScored(HookPortfolioItemScoredEventInterface $hookEvent);
}
