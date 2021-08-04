<?php

/* For licensing terms, see /license.txt */

/**
 * Interface HookDocumentItemActionObserverInterface.
 */
interface HookDocumentItemActionObserverInterface extends HookObserverInterface
{
    /**
     * @param \HookDocumentItemActionEventInterface $hookvent
     *
     * @return mixed
     */
    public function notifyDocumentItemAction(HookDocumentItemActionEventInterface $hookvent);
}
