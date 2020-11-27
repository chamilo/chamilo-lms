<?php

namespace spec\XApi\LrsBundle;

use PhpSpec\ObjectBehavior;

class XApiLrsBundleSpec extends ObjectBehavior
{
    function it_is_a_bundle()
    {
        $this->shouldHaveType('Symfony\Component\HttpKernel\Bundle\Bundle');
    }
}
