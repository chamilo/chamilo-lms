<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookResubscribeEventInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookResubscribeEventInterface extends HookEventInterface
{
    /**
     * Update all the observers.
     *
     * @param int $type
     *
     * @return int
     */
    public function notifyResubscribe($type);
}
