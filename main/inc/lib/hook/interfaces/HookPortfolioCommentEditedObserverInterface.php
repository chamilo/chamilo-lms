<?php

/* For licensing terms, see /license.txt */

interface HookPortfolioCommentEditedObserverInterface extends HookObserverInterface
{
    public function hookCommentEdited(HookPortfolioCommentEditedEventInterface $hookEvent);
}
