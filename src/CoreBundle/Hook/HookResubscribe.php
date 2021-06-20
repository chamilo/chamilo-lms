<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookResubscribeEventInterface;
use Chamilo\CoreBundle\Hook\Interfaces\HookResubscribeObserverInterface;
use Doctrine\ORM\EntityManager;
use SplObjectStorage;

/**
 * Class HookResubscribe.
 *
 * @var SplObjectStorage
 */
class HookResubscribe extends HookEvent implements HookResubscribeEventInterface
{
    /**
     * Constructor.
     */
    protected function __construct(EntityManager $entityManager)
    {
        parent::__construct('HookResubscribe', $entityManager);
    }

    /**
     * Update all the observers.
     *
     * @param int $type
     *
     * @return int
     */
    public function notifyResubscribe($type)
    {
        /** @var HookResubscribeObserverInterface $observer */
        $this->eventData['type'] = $type;

        foreach ($this->observers as $observer) {
            $observer->hookResubscribe($this);
        }

        return 1;
    }
}
