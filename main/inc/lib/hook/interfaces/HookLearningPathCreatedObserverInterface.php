<?php

/* For licensing terms, see /license.txt */

interface HookLearningPathCreatedObserverInterface extends HookObserverInterface
{
    public function hookCreated(HookLearningPathCreatedEventInterface $hookEvent);
}
