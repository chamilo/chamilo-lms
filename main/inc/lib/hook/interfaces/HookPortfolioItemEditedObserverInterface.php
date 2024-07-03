<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioItemEditedObserverInterface extends HookObserverInterface
{
    public function hookItemEdited(HookPortfolioItemEditedEventInterface $hookEvent);
}
