<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookResubscribe.
 *
 * @var \SplObjectStorage
 */
class HookResubscribe extends HookEvent implements HookResubscribeEventInterface
{
    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct('HookResubscribe');
    }

    /**
     * Update all the observers.
     *
     * @param int $type
     *
     * @return int
     */
    public function notifyResubscribe($type)
    {
        /** @var \HookResubscribeObserverInterface $observer */
        $this->eventData['type'] = $type;
        foreach ($this->observers as $observer) {
            $observer->hookResubscribe($this);
        }

        return 1;
    }
}
