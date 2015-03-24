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
     * @param   int     $type
     *
     * @return array    User ids
     */
    public function notifyCreateUser($type)
    {
        /** @var \HookCreateUserObserverInterface $observer */
        $this->eventData['type'] = $type;
        $userIds = array();
        foreach ($this->observers as $observer) {
            $userId = $observer->hookCreateUser($this);
            if ($userId !== false) {
                $userIds[] = $userId;
            }
        }
        return $userIds;
    }
}
