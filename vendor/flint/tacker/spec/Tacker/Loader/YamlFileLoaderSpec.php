<?php

namespace spec\Tacker\Loader;

use Prophecy\Argument;
use Symfony\Component\Config\FileLocator;

class YamlFileLoaderSpec extends \PhpSpec\ObjectBehavior
{
    /**
     * @param Tacker\ResourceCollection $resources
     */
    function let($resources)
    {
        $locator = new FileLocator(array(__DIR__ . '/../Fixtures/yaml'));

        $this->beConstructedWith($locator, $resources);
    }

    function it_supports_inherited_configs()
    {
        $this->load('inherit.yml')->shouldReturn(array('hello' => 'world'));
    }

    function it_supports_multiple_inherited_configs()
    {
        $this->load('multiple_inherited.yml')->shouldReturn(array(
            "hello" => "world",
            "multiple" => true,
        ));
    }
}
