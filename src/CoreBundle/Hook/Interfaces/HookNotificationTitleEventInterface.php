<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains Hook event interface for notification title.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookNotificationTitleEventInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
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
