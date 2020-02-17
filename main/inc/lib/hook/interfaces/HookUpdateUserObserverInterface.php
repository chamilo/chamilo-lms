<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 *
 * @package chamilo.library.hook
 */

/**
 * Interface UpdateUserHookInterface.
 */
interface HookUpdateUserObserverInterface extends HookObserverInterface
{
    /**
     * @return int
     */
    public function hookUpdateUser(HookUpdateUserEventInterface $hook);
}
