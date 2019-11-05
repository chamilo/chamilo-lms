<?php
/* For licensing terms, see /license.txt */

/**
 * Interface CheckLoginCredentialsHookEventInterface.
 */
interface CheckLoginCredentialsHookEventInterface extends HookEventInterface
{
    /**
     * Call to all observers.
     *
     * @return bool
     */
    public function notifyLoginCredentials();
}
