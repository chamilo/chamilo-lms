<?php

namespace spec\Behat\MinkExtension\ServiceContainer\Driver;

use PhpSpec\ObjectBehavior;

class SauceLabsFactorySpec extends ObjectBehavior
{
    function it_is_a_driver_factory()
    {
        $this->shouldHaveType('Behat\MinkExtension\ServiceContainer\Driver\DriverFactory');
    }

    function it_is_named_sauce_labs()
    {
        $this->getDriverName()->shouldReturn('sauce_labs');
    }

    function it_supports_javascript()
    {
        $this->supportsJavascript()->shouldBe(true);
    }
}
