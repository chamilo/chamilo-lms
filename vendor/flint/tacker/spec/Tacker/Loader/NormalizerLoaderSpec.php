<?php

namespace spec\Tacker\Loader;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NormalizerLoaderSpec extends ObjectBehavior
{
    /**
     * @param Symfony\Component\Config\Loader\LoaderInterface $loader
     * @param Tacker\Normalizer $normalizer
     */
    function let($loader, $normalizer)
    {
        $this->beConstructedWith($loader, $normalizer);
    }

    function it_is_symfony_loader()
    {
        $this->shouldHaveType('Symfony\Component\Config\Loader\Loader');
    }

    function it_normalizes_loaded_parameters($loader, $normalizer)
    {
        $loader->load('config.json', null)->willReturn(array(
            'hello' => 'world',
            'world' => array(
                'milkyway' => array('earth'),
            ),
        ));

        $normalizer->normalize('earth')->shouldBeCalled()->willReturnArgument();
        $normalizer->normalize('world')->shouldBeCalled()->willReturnArgument();

        $this->load('config.json')->shouldReturn(array(
            'hello' => 'world',
            'world' => array(
                'milkyway' => array('earth'),
            ),
        ));
    }
}
