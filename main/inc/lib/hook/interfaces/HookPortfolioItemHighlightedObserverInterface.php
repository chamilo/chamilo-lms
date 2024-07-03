<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioItemHighlightedObserverInterface extends HookObserverInterface
{
    public function hookItemHighlighted(HookPortfolioItemHighlightedEventInterface $hookEvent);
}
