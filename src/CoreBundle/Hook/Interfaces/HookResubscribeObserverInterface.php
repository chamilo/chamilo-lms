<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface ResubscribeHookInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface HookResubscribeObserverInterface extends HookObserverInterface
{
    /**
     * @param HookResubscribeEventInterface $hook
     *
     * @return int
     */
    public function hookResubscribe(HookResubscribeEventInterface $hook);
}
