<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookCreateUser
 * @var \SplObjectStorage $observers
 */
class HookCreateUser extends HookEvent implements HookCreateUserEventInterface
{
    /**
     * Constructor
     */
    protected function __construct()
    {
        parent::__construct('HookCreateUser');
    }

    /**
     * Update all the observers
     * @param int $type
     *
     * @return int
     */
    public function notifyCreateUser($type)
    {
        /** @var \HookCreateUserObserverInterface $observer */
        $this->eventData['type'] = $type;
        foreach ($this->observers as $observer) {
            $observer->hookCreateUser($this);
        }
        return 1;
    }
}
