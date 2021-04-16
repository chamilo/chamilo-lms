<?php

/* For licensing terms, see /license.txt */

interface HookDocumentItemViewEventInterface extends HookEventInterface
{
    /**
     * @return array
     */
    public function notifyDocumentItemView(): array;
}
