<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioItemVisibilityEventInterface extends HookEventInterface
{
    /**
     * @return void
     */
    public function notifyItemVisibility();
}
