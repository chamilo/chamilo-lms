<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookNotificationContentEventInterface
 */
interface HookNotificationContentEventInterface extends HookEventInterface
{
    /**
     * @param int $type
     * @return int
     */
    public function notifyNotificationContent($type);
}
