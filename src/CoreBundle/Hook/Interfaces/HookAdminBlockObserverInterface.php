<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookAdminBlockObserverInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookAdminBlockObserverInterface extends HookObserverInterface
{
    /**
     * @param HookAdminBlockEventInterface $hook
     *
     * @return int
     */
    public function hookAdminBlock(HookAdminBlockEventInterface $hook);
}
