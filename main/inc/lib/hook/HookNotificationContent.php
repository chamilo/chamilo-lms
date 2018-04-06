<?php
/* For licensing terms, see /license.txt */

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
     */
    protected function __construct()
    {
        parent::__construct('HookNotificationContent');
    }

    /**
     * @param int $type
     *
     * @return array|null
     */
    public function notifyNotificationContent($type)
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

        return null;
    }
}
