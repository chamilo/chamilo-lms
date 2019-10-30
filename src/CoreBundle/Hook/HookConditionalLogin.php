<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookConditionalLoginEventInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class HookConditionalLogin.
 *
 * Hook to implement Conditional Login.
 */
class HookConditionalLogin extends HookEvent implements HookConditionalLoginEventInterface
{
    /**
     * HookConditionalLogin constructor.
     *
     * @param EntityManager $entityManager
     */
    protected function __construct(EntityManager $entityManager)
    {
        parent::__construct('HookConditionalLogin', $entityManager);
    }

    /**
     * Notify to all hook observers.
     *
     * @return array
     */
    public function notifyConditionalLogin(): array
    {
        $conditions = [];

        /** @var HookConditionalLoginObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $conditions[] = $observer->hookConditionalLogin($this);
        }

        return $conditions;
    }
}
