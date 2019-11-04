<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains Hook observer interface for notification content.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookNotificationContentObserverInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookNotificationContentObserverInterface extends HookObserverInterface
{
    /**
     * @return array
     */
    public function hookNotificationContent(HookNotificationContentEventInterface $hook);
}
