<?php

namespace spec\Tacker\Loader;

use Prophecy\Argument;
use Symfony\Component\Config\FileLocator;

class IniFileLoaderSpec extends \PhpSpec\ObjectBehavior
{
    /**
     * @param Tacker\ResourceCollection $resources
     */
    function let($resources)
    {
        $locator = new FileLocator(array(__DIR__ . '/../Fixtures/ini'));

        $this->beConstructedWith($locator, $resources);
    }

    function it_supports_inherited_configs()
    {
        if (preg_match('{hiphop}', phpversion())) {
            // HHVM does not support @ in ini files.
            return;
        }

        $this->load('inherit.ini')->shouldReturn(array(
            'hello' => 'world',
        ));
    }
}
