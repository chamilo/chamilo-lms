<?php

/* For licensing terms, see /license.txt */

interface HookDocumentItemActionEventInterface extends HookEventInterface
{
    public function notifyDocumentItemAction($type);
}
