<?php

/* For licensing terms, see /license.txt */

/**
 * Interface HookDocumentItemActionEventInterface.
 */
interface HookDocumentItemActionEventInterface extends HookEventInterface
{
    /**
     * @param $type
     *
     * @return mixed
     */
    public function notifyDocumentItemAction($type);
}
