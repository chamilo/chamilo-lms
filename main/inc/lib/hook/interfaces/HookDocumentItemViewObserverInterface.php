<?php

/* For licensing terms, see /license.txt */

/**
 * Interface HookDocumentItemViewObserverInterface.
 */
interface HookDocumentItemViewObserverInterface extends HookObserverInterface
{
    /**
     * @param HookDocumentItemViewEventInterface $hookvent
     *
     * @return string
     */
    public function notifyDocumentItemView(HookDocumentItemViewEventInterface $hookvent): string;
}
