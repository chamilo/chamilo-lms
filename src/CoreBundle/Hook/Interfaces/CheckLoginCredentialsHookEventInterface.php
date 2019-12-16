<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface CheckLoginCredentialsHookEventInterface.
 */
interface CheckLoginCredentialsHookEventInterface extends HookEventInterface
{
    /**
     * Call to all observers.
     */
    public function notifyLoginCredentials(): bool;
}
