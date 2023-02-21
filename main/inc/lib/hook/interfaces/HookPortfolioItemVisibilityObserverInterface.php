<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioItemVisibilityObserverInterface extends HookObserverInterface
{
    /**
     * @return void
     */
    public function hookItemVisibility(HookPortfolioItemVisibilityEventInterface $event);
}
