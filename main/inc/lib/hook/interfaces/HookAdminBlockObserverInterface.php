<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 *
 * @package chamilo.library.hook
 */

/**
 * Interface HookAdminBlockObserverInterface.
 */
interface HookAdminBlockObserverInterface extends HookObserverInterface
{
    /**
     * @return int
     */
    public function hookAdminBlock(HookAdminBlockEventInterface $hook);
}
