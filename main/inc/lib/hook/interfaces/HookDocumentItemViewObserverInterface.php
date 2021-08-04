<?php

/* For licensing terms, see /license.txt */

/**
 * Interface HookDocumentItemViewObserverInterface.
 */
interface HookDocumentItemViewObserverInterface extends HookObserverInterface
{
    public function notifyDocumentItemView(HookDocumentItemViewEventInterface $hookvent): string;
}
