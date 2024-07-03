<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioItemViewedObserverInterface extends HookObserverInterface
{
    public function hookItemViewed(HookPortfolioItemViewedEventInterface $hookEvent);
}
