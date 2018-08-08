<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookUpdateUser.
 *
 * @var \SplObjectStorage
 */
class HookUpdateUser extends HookEvent implements HookUpdateUserEventInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('HookUpdateUser');
    }

    /**
     * Update all the observers.
     *
     * @param int $type
     *
     * @return int
     */
    public function notifyUpdateUser($type)
    {
        $this->eventData['type'] = $type;

        /** @var HookUpdateUserObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookUpdateUser($this);
        }

        return 1;
    }
}
