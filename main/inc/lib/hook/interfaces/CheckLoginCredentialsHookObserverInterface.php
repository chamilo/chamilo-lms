<?php
/* For licensing terms, see /license.txt */

/**
 * Interface CheckLoginCredentialsHookObserverInterface.
 */
interface CheckLoginCredentialsHookObserverInterface extends HookObserverInterface
{
    /**
     * @return bool
     */
    public function checkLoginCredentials(CheckLoginCredentialsHookEventInterface $event);
}
