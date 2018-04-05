<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 *
 * @package chamilo.library.hook
 */

/**
 * Interface SkypeHookInterface.
 */
interface HookSkypeObserverInterface extends HookObserverInterface
{
    /**
     * @param HookSkypeObserverInterface $hook
     *
     * @return int
     */
    public function hookEventSkype(HookSkypeEventInterface $hook);
}
