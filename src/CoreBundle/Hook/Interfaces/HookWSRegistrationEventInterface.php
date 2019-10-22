<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookWSRegistrationEventInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookWSRegistrationEventInterface extends HookEventInterface
{
    /**
     * @param int $type
     *
     * @return int
     */
    public function notifyWSRegistration($type);
}
