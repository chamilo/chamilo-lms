<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioItemViewedEventInterface extends HookEventInterface
{
    public function notifyItemViewed(): void;
}
