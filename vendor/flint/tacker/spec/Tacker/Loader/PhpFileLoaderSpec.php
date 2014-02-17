<?php

namespace spec\Tacker\Loader;

use Symfony\Component\Config\FileLocator;

class PhpFileLoaderSpec extends \PhpSpec\ObjectBehavior
{
    /**
     * @param Tacker\ResourceCollection $resources
     */
    function let($resources)
    {
        $locator = new FileLocator(array(__DIR__ . '/../Fixtures/php'));
        $this->beConstructedWith($locator, $resources);
    }

    function it_supports_inherited_configs()
    {
        $this->load('inherit.php')->shouldReturn(array(
            'hello' => 'world',
        ));
    }

    function it_supports_multiple_inherited_configs()
    {
        $this->load('multiple_inherited.php')->shouldReturn(array(
            "hello" => "world",
            "multiple" => true,
        ));
    }
}
