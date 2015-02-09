<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains the Hook Event class for Content of Notifications
 */

/**
 * Class HookNotificationContent
 */
class HookNotificationContent extends HookEvent implements HookNotificationContentEventInterface
{

    /**
     * Construct
     */
    protected function __construct()
    {
        parent::__construct('HookNotificationContent');
    }

    /**
     * @param int $type
     * @return int
     */
    public function notifyNotificationContent($type)
    {
        /** @var \HookNotificationContentObserverInterface $observer */
        if (isset($this->eventData['content'])) {
            $this->eventData['type'] = $type;
            foreach ($this->observers as $observer) {
                $data = $observer->hookNotificationContent($this);
                if (isset($data['content'])) {
                    $this->setEventData($data);
                }
            }

            return $this->eventData;
        }

        return null;
    }
}