<?php

namespace spec\Behat\MinkExtension\ServiceContainer\Driver;

use PhpSpec\ObjectBehavior;

class Selenium2FactorySpec extends ObjectBehavior
{
    function it_is_a_driver_factory()
    {
        $this->shouldHaveType('Behat\MinkExtension\ServiceContainer\Driver\DriverFactory');
    }

    function it_is_named_selenium2()
    {
        $this->getDriverName()->shouldReturn('selenium2');
    }

    function it_supports_javascript()
    {
        $this->supportsJavascript()->shouldBe(true);
    }
}
