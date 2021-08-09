<?php

/* For licensing terms, see /license.txt */

/**
 * Interface HookPortfolioItemCommentedEventInterface.
 */
interface HookPortfolioItemCommentedEventInterface extends HookEventInterface
{
    /**
     * @return void
     */
    public function notifyItemCommented();
}
