<?php

namespace spec\Tacker\Normalizer;

class ChainNormalizerSpec extends \PhpSpec\ObjectBehavior
{
    /**
     * @param Tacker\Normalizer $first
     * @param Tacker\Normalizer $second
     */
    function let($first, $second)
    {
        $this->beConstructedWith(array($first, $second));
    }

    function it_calls_in_a_chain($first, $second)
    {
        $first->normalize('original')->shouldBeCalled()->willReturn('first');
        $second->normalize('first')->shouldBeCalled()->willReturn('second');

        $this->normalize('original')->shouldReturn('second');
    }
}
