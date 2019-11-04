<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface UpdateUserHookInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookUpdateUserObserverInterface extends HookObserverInterface
{
    /**
     * @return int
     */
    public function hookUpdateUser(HookUpdateUserEventInterface $hook);
}
