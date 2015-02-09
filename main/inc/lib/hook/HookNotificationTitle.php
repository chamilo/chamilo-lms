<?php
/* For licensing terms, see /license.txt */
/**
 * This file contains the Hook Event class for Title of Notifications
 */

/**
 * Class HookNotificationTitle
 */
class HookNotificationTitle extends HookEvent implements HookNotificationTitleEventInterface
{

    /**
     * Construct
     */
    protected function __construct()
    {
        parent::__construct('HookNotificationTitle');
    }

    /**
     * @param int $type
     * @return int
     */
    public function notifyNotificationTitle($type)
    {
        /** @var \HookNotificationTitleObserverInterface $observer */
        if (isset($this->eventData['title'])) {
            $this->eventData['type'] = $type;
            foreach ($this->observers as $observer) {
                $data = $observer->hookNotificationTitle($this);
                if (isset($data['title'])) {
                    $this->setEventData($data);
                }
            }

            return $this->eventData;
        }

        return null;
    }
}