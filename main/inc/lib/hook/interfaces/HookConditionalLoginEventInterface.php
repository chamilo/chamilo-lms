<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookConditionalLoginEventInterface.
 */
interface HookConditionalLoginEventInterface extends HookEventInterface
{
    /**
     * Call Conditional Login hooks.
     *
     * @return array
     */
    public function notifyConditionalLogin();
}
