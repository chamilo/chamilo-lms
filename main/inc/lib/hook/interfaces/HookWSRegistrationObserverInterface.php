<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains all Hook interfaces and their relation.
 * They are used for Hook classes.
 *
 * @package chamilo.library.hook
 */

/**
 * Interface HookWSRegistrationObserverInterface.
 */
interface HookWSRegistrationObserverInterface extends HookObserverInterface
{
    /**
     * @return int
     */
    public function hookWSRegistration(HookWSRegistrationEventInterface $hook);
}
