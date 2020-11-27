<?php

namespace spec\XApi\LrsBundle\DependencyInjection;

use PhpSpec\ObjectBehavior;

class XApiLrsExtensionSpec extends ObjectBehavior
{
    function it_is_a_di_extension()
    {
        $this->shouldHaveType('Symfony\Component\DependencyInjection\Extension\ExtensionInterface');
    }

    function its_alias_is_xapi_lrs()
    {
        $this->getAlias()->shouldReturn('xapi_lrs');
    }
}
