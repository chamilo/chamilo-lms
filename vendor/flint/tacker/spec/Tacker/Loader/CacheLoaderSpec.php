<?php

namespace spec\Tacker\Loader;

use Prophecy\Argument;

class CacheLoaderSpec extends \PhpSpec\ObjectBehavior
{
    /**
     * @param Symfony\Component\Config\Loader\LoaderInterface $loader
     * @param Tacker\Normalizer $normalizer
     * @param Tacker\ResourceCollection $resources
     */
    function let($loader, $resources)
    {
        $this->beConstructedWith($loader, $resources);

        // make sure that the directory used for specs are clean
        @array_map('unlink', glob(sys_get_temp_dir() . '/tacker/*'));
    }

    function it_is_symfony_loader()
    {
        $this->shouldHaveType('Symfony\Component\Config\Loader\Loader');
    }

    /**
     * @param Symfony\Component\Config\Resource\ResourceInterface $resource
     */
    function it_reloads_config_when_resources_change_in_debug($resource, $loader, $resources)
    {
        $this->setDebug(true);
        $this->setCacheDir(sys_get_temp_dir() . '/tacker');

        $resources->all()->willReturn(array($resource));
        $resource->isFresh(Argument::any())->willReturn(false);

        $loader->load('debug.json')->willReturn(array('hello' => 'world'))
            ->shouldBeCalledTimes(2);

        $this->load('debug.json');
        $this->load('debug.json');
    }

    function it_caches_config($loader, $pimple, $normalizer)
    {
        $this->setCacheDir(sys_get_temp_dir() . '/tacker');

        $loader->load('config.json')->willReturn(array('hello' => 'world'))
            ->shouldBeCalledTimes(1);

        $this->load('config.json');
        $this->load('config.json');
    }

    function it_loads_config_file($loader)
    {
        $loader->load('config.json')->willReturn(array('hello' => 'world'))
            ->shouldBeCalled();

        $this->load('config.json')->shouldReturn(array(
            'hello' => 'world',
        ));
    }

    function it_allows_setting_debug_and_cache_dir()
    {
        $this->getDebug()->shouldReturn(false);
        $this->getCacheDir()->shouldReturn(null);

        $this->setDebug(true);
        $this->setCacheDir('/path');

        $this->getDebug()->shouldReturn(true);
        $this->getCacheDir()->shouldReturn('/path');
    }
}
