<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains Hook observer interface for notification title.
 *
 * @package chamilo.library.hook
 */

/**
 * Interface HookNotificationTitleObserverInterface.
 */
interface HookNotificationTitleObserverInterface extends HookObserverInterface
{
    /**
     * @return array
     */
    public function hookNotificationTitle(HookNotificationTitleEventInterface $hook);
}
