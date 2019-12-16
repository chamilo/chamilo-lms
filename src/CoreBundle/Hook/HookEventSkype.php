<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookSkypeEventInterface;
use Chamilo\CoreBundle\Hook\Interfaces\HookSkypeObserverInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class HookEventSkype.
 */
class HookEventSkype extends HookEvent implements HookSkypeEventInterface
{
    /**
     * Constructor.
     */
    protected function __construct(EntityManager $entityManager)
    {
        parent::__construct('HookEventSkype', $entityManager);
    }

    /**
     * Update all the observers.
     *
     * @param int $type
     *
     * @return int
     */
    public function notifySkype($type)
    {
        /** @var HookSkypeObserverInterface $observer */
        $this->eventData['type'] = $type;

        foreach ($this->observers as $observer) {
            $observer->hookEventSkype($this);
        }

        return 1;
    }
}
