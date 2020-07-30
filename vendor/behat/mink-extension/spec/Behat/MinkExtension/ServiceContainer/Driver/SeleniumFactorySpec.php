<?php

namespace spec\Behat\MinkExtension\ServiceContainer\Driver;

use PhpSpec\ObjectBehavior;

class SeleniumFactorySpec extends ObjectBehavior
{
    function it_is_a_driver_factory()
    {
        $this->shouldHaveType('Behat\MinkExtension\ServiceContainer\Driver\DriverFactory');
    }

    function it_is_named_selenium()
    {
        $this->getDriverName()->shouldReturn('selenium');
    }

    function it_supports_javascript()
    {
        $this->supportsJavascript()->shouldBe(true);
    }
}
