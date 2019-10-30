<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains a Hook Event class for Admin Block.
 *
 * @package chamilo.library.hook
 */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookAdminBlockEventInterface;
use Doctrine\ORM\EntityManager;

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
     * @param EntityManager $entityManager
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
        /** @var \HookAdminBlockObserverInterface $observer */
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
