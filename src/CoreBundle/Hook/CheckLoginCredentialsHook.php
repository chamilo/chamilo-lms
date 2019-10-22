<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\CheckLoginCredentialsHookEventInterface;
use Chamilo\CoreBundle\Hook\Interfaces\CheckLoginCredentialsHookObserverInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class CheckLoginCredentialsHook.
 */
class CheckLoginCredentialsHook extends HookEvent implements CheckLoginCredentialsHookEventInterface
{
    /**
     * CheckLoginCredentialsHook constructor.
     *
     * @param EntityManager $entityManager
     */
    protected function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct('CheckLoginCredentialsHook');
    }

    /**
     * Call to all observers.
     *
     * @return bool
     */
    public function notifyLoginCredentials(): bool
    {
        /** @var CheckLoginCredentialsHookObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $isChecked = $observer->checkLoginCredentials($this);

            if ($isChecked) {
                return true;
            }
        }

        return false;
    }
}
