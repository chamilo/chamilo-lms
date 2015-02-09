<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookNotificationContentObserverInterface
 */
interface HookNotificationContentObserverInterface extends HookObserverInterface
{
    /**
     * @param HookNotificationContentEventInterface $hook
     * @return int
     */
    public function hookNotificationContent(HookNotificationContentEventInterface $hook);
}
