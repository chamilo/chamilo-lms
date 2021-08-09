<?php

/* For licensing terms, see /license.txt */

/**
 * Interface HookPortfolioItemAddedEventInterface.
 */
interface HookPortfolioItemAddedEventInterface extends HookEventInterface
{
    /**
     * @return void
     */
    public function notifyItemAdded();
}
