<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookUpdateUser
 *
 * @var \SplObjectStorage $observers
 */
class HookUpdateUser extends HookEvent implements HookUpdateUserEventInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('HookUpdateUser');
    }

    /**
     * Update all the observers
     * @param int $type
     * @return int
     */
    public function notifyUpdateUser($type)
    {
        /** @var \HookUpdateUserObserverInterface $observer */
        $this->eventData['type'] = $type;
        foreach ($this->observers as $observer) {
            $observer->hookUpdateUser($this);
        }

        return 1;
    }
}
