<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains Hook event interface for notification content.
 *
 * @package chamilo.library.hook
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookNotificationContentEventInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookNotificationContentEventInterface extends HookEventInterface
{
    /**
     * @param int $type
     */
    public function notifyNotificationContent($type): array;
}
