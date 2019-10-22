<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface SkypeHookInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookSkypeObserverInterface extends HookObserverInterface
{
    /**
     * @param HookSkypeEventInterface $hook
     *
     * @return int
     */
    public function hookEventSkype(HookSkypeEventInterface $hook);
}
