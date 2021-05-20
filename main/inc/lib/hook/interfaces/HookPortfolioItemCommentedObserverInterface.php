<?php

/* For licensing terms, see /license.txt */

/**
 * Interface HookPortfolioItemCommentedObserverInterface.
 */
interface HookPortfolioItemCommentedObserverInterface extends HookObserverInterface
{
    /**
     * @param \HookPortfolioItemCommentedEventInterface $hookEvent
     *
     * @return mixed
     */
    public function hookItemCommented(HookPortfolioItemCommentedEventInterface $hookEvent);
}
