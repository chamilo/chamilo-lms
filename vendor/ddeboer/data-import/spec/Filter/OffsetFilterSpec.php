<?php

namespace spec\Ddeboer\DataImport\Filter;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OffsetFilterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Filter\OffsetFilter');
    }

    function it_does_not_limit_by_default()
    {
        $this->__invoke(['content'])->shouldReturn(true);
    }

    function it_limits_until_the_start_offset()
    {
        $this->beConstructedWith(1);

        $this->__invoke(['content'])->shouldReturn(false);
        $this->__invoke(['content'])->shouldReturn(true);
    }

    function it_limits_when_max_is_reached()
    {
        $this->beConstructedWith(0, 2);

        $this->__invoke(['content'])->shouldReturn(true);
        $this->__invoke(['content'])->shouldReturn(true);
        $this->__invoke(['content'])->shouldReturn(false);
    }
}
