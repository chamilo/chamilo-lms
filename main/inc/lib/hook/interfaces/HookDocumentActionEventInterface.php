<?php

/* For licensing terms, see /license.txt */

/**
 * Interface HookDocumentActionEventInterface.
 */
interface HookDocumentActionEventInterface extends HookEventInterface
{
    /**
     * @param $type
     *
     * @return mixed
     */
    public function notifyDocumentAction($type);
}
