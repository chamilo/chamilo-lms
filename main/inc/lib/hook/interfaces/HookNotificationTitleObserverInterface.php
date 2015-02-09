<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookNotificationTitleObserverInterface
 */
interface HookNotificationTitleObserverInterface extends HookObserverInterface
{
    /**
     * @param HookNotificationTitleEventInterface $hook
     * @return int
     */
    public function hookNotificationTitle(HookNotificationTitleEventInterface $hook);
}
