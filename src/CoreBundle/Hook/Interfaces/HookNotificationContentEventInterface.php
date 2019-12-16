<?php

/* For licensing terms, see /license.txt */

/**
 * This file contains Hook event interface for notification content.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookNotificationContentEventInterface.
 */
interface HookNotificationContentEventInterface extends HookEventInterface
{
    /**
     * @param int $type
     */
    public function notifyNotificationContent($type): array;
}
