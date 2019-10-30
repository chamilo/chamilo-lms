<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookNotificationContentEventInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class HookNotificationContent
 * Hook Event class for Content format of Notifications.
 *
 * @package chamilo.library.hook
 */
class HookNotificationContent extends HookEvent implements HookNotificationContentEventInterface
{
    /**
     * Construct.
     *
     * @param EntityManager $entityManager
     */
    protected function __construct(EntityManager $entityManager)
    {
        parent::__construct('HookNotificationContent', $entityManager);
    }

    /**
     * @param int $type
     *
     * @return array
     */
    public function notifyNotificationContent($type): array
    {
        /** @var \HookNotificationContentObserverInterface $observer */
        // Check if exists data content
        if (isset($this->eventData['content'])) {
            // Save data type
            $this->eventData['type'] = $type;
            // Check for hook all registered observers
            foreach ($this->observers as $observer) {
                $data = $observer->hookNotificationContent($this);
                // Check if isset content
                if (isset($data['content'])) {
                    // Set data from hook observer data
                    $this->setEventData($data);
                }
            }

            return $this->eventData;
        }

        return [];
    }
}
