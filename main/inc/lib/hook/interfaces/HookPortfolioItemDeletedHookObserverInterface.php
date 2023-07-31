<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioItemDeletedHookObserverInterface extends HookObserverInterface
{
    public function hookItemDeleted(HookPortfolioItemDeletedEventInterface $hookEvent);
}
