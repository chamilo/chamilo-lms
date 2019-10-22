<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Hook;

use Chamilo\CoreBundle\Hook\Interfaces\HookSkypeEventInterface;
use Chamilo\CoreBundle\Hook\Interfaces\HookSkypeObserverInterface;

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
        /** @var HookSkypeObserverInterface $observer */
        $this->eventData['type'] = $type;

        foreach ($this->observers as $observer) {
            $observer->hookEventSkype($this);
        }

        return 1;
    }
}
