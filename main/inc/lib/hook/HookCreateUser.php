<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookCreateUser.
 *
 * @var \SplObjectStorage
 */
class HookCreateUser extends HookEvent implements HookCreateUserEventInterface
{
    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct('HookCreateUser');
    }

    /**
     * Update all the observers.
     *
     * @param int $type
     *
     * @return int
     */
    public function notifyCreateUser($type)
    {
        $this->eventData['type'] = $type;

        /** @var HookCreateUserObserverInterface $observer */
        foreach ($this->observers as $observer) {
            $observer->hookCreateUser($this);
        }

        return 1;
    }
}
