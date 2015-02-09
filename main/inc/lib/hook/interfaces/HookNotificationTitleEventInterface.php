<?php
/* For licensing terms, see /license.txt */

/**
 * Interface HookNotificationTitleEventInterface
 */
interface HookNotificationTitleEventInterface extends HookEventInterface
{
    /**
     * @param int $type
     * @return int
     */
    public function notifyNotificationTitle($type);
}
