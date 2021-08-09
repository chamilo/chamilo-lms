<?php

/* For licensing terms, see /license.txt */

interface HookDocumentItemViewEventInterface extends HookEventInterface
{
    public function notifyDocumentItemView(): array;
}
