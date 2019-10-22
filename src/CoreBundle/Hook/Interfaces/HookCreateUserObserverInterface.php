<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface CreateUserHookInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookCreateUserObserverInterface extends HookObserverInterface
{
    /**
     * @param HookCreateUserEventInterface $hook
     *
     * @return int
     */
    public function hookCreateUser(HookCreateUserEventInterface $hook);
}
