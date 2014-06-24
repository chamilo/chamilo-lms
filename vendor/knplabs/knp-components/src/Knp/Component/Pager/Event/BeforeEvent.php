<?php

namespace Knp\Component\Pager\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Specific Event class for paginator
 */
class BeforeEvent extends Event
{
    private $eventDispatcher;

    public function __construct($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }
}
