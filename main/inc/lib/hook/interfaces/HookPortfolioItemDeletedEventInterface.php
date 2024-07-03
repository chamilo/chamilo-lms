<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioItemDeletedEventInterface extends HookEventInterface
{
    public function notifyItemDeleted();
}
