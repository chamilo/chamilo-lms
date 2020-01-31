<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains Hook observer interface for notification content.
 *
 * @package chamilo.library.hook
 */

/**
 * Interface HookNotificationContentObserverInterface.
 */
interface HookNotificationContentObserverInterface extends HookObserverInterface
{
    /**
     * @return array
     */
    public function hookNotificationContent(HookNotificationContentEventInterface $hook);
}
