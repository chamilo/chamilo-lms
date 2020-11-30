<?php

/* For licensing terms, see /license.txt */

interface HookDocumentActionEventInterface extends HookEventInterface
{
    public function notifyDocumentAction($type);
}
