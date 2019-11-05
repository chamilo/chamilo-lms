<?php
/* For licensing terms, see /license.txt */

/**
 * Interface CheckLoginCredentialsHookObserverInterface.
 */
interface CheckLoginCredentialsHookObserverInterface extends HookObserverInterface
{
    /**
     * @param CheckLoginCredentialsHookEventInterface $event
     *
     * @return bool
     */
    public function checkLoginCredentials(CheckLoginCredentialsHookEventInterface $event);
}
