<?php
/* For licensing terms, see /license.txt */

/**
 * Class HookEventSkype.
 *
 * @var \SplObjectStorage
 */
class HookEventSkype extends HookEvent implements HookSkypeEventInterface
{
    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct('HookEventSkype');
    }

    /**
     * Update all the observers.
     *
     * @param int $type
     *
     * @return int
     */
    public function notifySkype($type)
    {
        /** @var \HookSkypeObserverInterface $observer */
        $this->eventData['type'] = $type;
        foreach ($this->observers as $observer) {
            $observer->hookEventSkype($this);
        }

        return 1;
    }
}
