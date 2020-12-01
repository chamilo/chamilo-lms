<?php

/* For licensing terms, see /license.txt */

/**
 * Interface HookDocumentActionObserverInterface.
 */
interface HookDocumentActionObserverInterface extends HookObserverInterface
{
    /**
     * @param \HookDocumentActionEventInterface $hookvent
     *
     * @return mixed
     */
    public function notifyDocumentAction(HookDocumentActionEventInterface $hookvent);
}
