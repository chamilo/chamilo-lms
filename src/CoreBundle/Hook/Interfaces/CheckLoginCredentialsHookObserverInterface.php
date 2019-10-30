<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook\Interfaces;

/**
 * Interface CheckLoginCredentialsHookObserverInterface.
 *
 * @package Chamilo\CoreBundle\Hook\Interfaces
 */
interface CheckLoginCredentialsHookObserverInterface extends HookObserverInterface
{
    /**
     * @param CheckLoginCredentialsHookEventInterface $event
     *
     * @return bool
     */
    public function checkLoginCredentials(CheckLoginCredentialsHookEventInterface $event): bool;
}
