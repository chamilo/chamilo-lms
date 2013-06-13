<?php

namespace Knp\Console;

use Symfony\Component\EventDispatcher\Event;
use Knp\Console\Application;

class ConsoleEvent extends Event
{
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function getApplication()
    {
        return $this->application;
    }
}