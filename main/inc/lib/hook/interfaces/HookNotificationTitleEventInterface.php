<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains Hook event interface for notification title.
 *
 * @package chamilo.library.hook
 */

/**
 * Interface HookNotificationTitleEventInterface.
 */
interface HookNotificationTitleEventInterface extends HookEventInterface
{
    /**
     * @param int $type
     *
     * @return array
     */
    public function notifyNotificationTitle($type);
}
