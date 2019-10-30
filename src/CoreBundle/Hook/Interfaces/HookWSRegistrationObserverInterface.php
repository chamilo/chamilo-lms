<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface HookWSRegistrationObserverInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookWSRegistrationObserverInterface extends HookObserverInterface
{
    /**
     * @param HookWSRegistrationEventInterface $hook
     *
     * @return int
     */
    public function hookWSRegistration(HookWSRegistrationEventInterface $hook);
}
