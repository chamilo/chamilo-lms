<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains Hook event interface for notification content.
 *
 * @package chamilo.library.hook
 */

/**
 * Interface HookNotificationContentEventInterface.
 */
interface HookNotificationContentEventInterface extends HookEventInterface
{
    /**
     * @param int $type
     *
     * @return array
     */
    public function notifyNotificationContent($type);
}
