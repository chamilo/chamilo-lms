<?php

/* For licensing terms, see /license.txt */

/**
 * Interface HookPortfolioItemAddedObserverInterface.
 */
interface HookPortfolioItemAddedObserverInterface extends HookObserverInterface
{
    /**
     * @param \HookPortfolioItemAddedEventInterface $hookEvent
     *
     * @return mixed
     */
    public function hookItemAdded(HookPortfolioItemAddedEventInterface $hookEvent);
}
