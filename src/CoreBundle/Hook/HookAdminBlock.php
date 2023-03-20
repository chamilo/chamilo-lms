<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookAdminBlockEventInterface;
use Chamilo\CoreBundle\Hook\Interfaces\HookAdminBlockObserverInterface;
use Doctrine\ORM\EntityManager;
use Exception;

/**
 * Class HookAdminBlock
 * This class is a Hook event implementing Admin Block Event interface.
 * This class is used to modify admin block by notifying Hook Observer for Admin Block.
 */
class HookAdminBlock extends HookEvent implements HookAdminBlockEventInterface
{
    /**
     * Constructor.
     *
     * @throws Exception
     */
    protected function __construct(EntityManager $entityManager)
    {
        parent::__construct('HookAdminBlock', $entityManager);
    }

    /**
     * Notify Hook observers for Admin Block event.
     *
     * @param int $type Set the type of hook event called.
     *                  0: HOOK_EVENT_TYPE_PRE, 1: HOOK_EVENT_TYPE_POST
     *
     * @return array|int
     */
    public function notifyAdminBlock($type)
    {
        /** @var HookAdminBlockObserverInterface $observer */
        // Save data
        if (isset($this->eventData['blocks'])) {
            $this->eventData['type'] = $type;
            // Call all registered hook observers for admin block
            foreach ($this->observers as $observer) {
                $data = $observer->hookAdminBlock($this);
                if (isset($data['blocks'])) {
                    // Get modified data
                    $this->eventData['blocks'] = $data['blocks'];
                }
            }

            return $this->eventData;
        }

        return 0;
    }
}
