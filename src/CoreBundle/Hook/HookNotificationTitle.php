<?php

/* For licensing terms, see /license.txt */
/**
 * This file contains the Hook Event class for Title of Notifications.
 */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookNotificationTitleEventInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class HookNotificationTitle.
 */
class HookNotificationTitle extends HookEvent implements HookNotificationTitleEventInterface
{
    /**
     * Construct.
     */
    protected function __construct(EntityManager $entityManager)
    {
        parent::__construct('HookNotificationTitle', $entityManager);
    }

    /**
     * @param int $type
     */
    public function notifyNotificationTitle($type): array
    {
        // Check if exists data title
        /*if (isset($this->eventData['title'])) {
            // Save data type
            $this->eventData['type'] = $type;
            // Check for hook all registered observers
            foreach ($this->observers as $observer) {
                // Get data from hook observer
                $data = $observer->hookNotificationTitle($this);
                // Check if isset data title
                if (isset($data['title'])) {
                    // Set data from hook observer data
                    $this->setEventData($data);
                }
            }

            return $this->eventData;
        }*/

        return [];
    }
}
