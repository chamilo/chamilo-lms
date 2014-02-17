<?php

namespace spec\Tacker\Loader;

use Prophecy\Argument;
use Symfony\Component\Config\FileLocator;

class JsonFileLoaderSpec extends \PhpSpec\ObjectBehavior
{
    /**
     * @param Tacker\ResourceCollection $resources
     */
    function let($resources)
    {
        $locator = new FileLocator(array(__DIR__ . '/../Fixtures/json'));

        $this->beConstructedWith($locator, $resources);
    }

    function it_supports_inherited_configs()
    {
        $this->load('inherit.json')->shouldReturn(array(
            'hello' => 'world',
        ));
    }

    function it_supports_multiple_inherited_configs()
    {
        $this->load('multiple_inherited.json')->shouldReturn(array(
            "hello" => "world",
            "multiple" => true,
        ));
    }
}
