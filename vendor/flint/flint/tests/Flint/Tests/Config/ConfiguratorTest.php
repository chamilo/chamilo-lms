<?php

namespace Flint\Tests\Config;

use Flint\Config\Configurator;
use Flint\Config\ResourceCollection;
use Pimple;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;

class ConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    const CACHE_CONTENT = <<<CONTENT
<?php \$parameters = array (
  'service_parameter' => 'hello',
);
CONTENT;

    public function setUp()
    {
        $this->loader = $this->getMock('Symfony\Component\Config\Loader\LoaderInterface');
        $this->loader->expects($this->any())->method('supports')->will($this->returnValue(true));

        $this->delegator = new DelegatingLoader(new LoaderResolver(array($this->loader)));
        $this->cacheFile = "/var/tmp/1058386122.php";

        $this->resources = new ResourceCollection;
    }

    public function tearDown()
    {
        @unlink($this->cacheFile);
        @unlink($this->cacheDilr.  '.meta');
    }

    public function testItBuilds()
    {
        $this->loader->expects($this->once())->method('load')->with($this->equalTo('config.json'))
            ->will($this->returnValue(array('service_parameter' => 'hello')));

        $pimple = new Pimple;

        $this->createConfigurator()->configure($pimple, 'config.json');

        $this->assertEquals('hello', $pimple['service_parameter']);
    }

    public function testAFreshCacheSkipsLoader()
    {
        // Create a fresh cache
        file_put_contents($this->cacheFile, static::CACHE_CONTENT);

        $pimple = new Pimple;

        $this->loader->expects($this->never())->method('load');

        $this->createConfigurator(false)->configure($pimple, 'config.json');

        $this->assertEquals('hello', $pimple['service_parameter']);
    }

    public function testStaleCacheWritesFile()
    {
        $this->loader->expects($this->once())->method('load')->with($this->equalTo('config.json'))->will($this->returnValue(array(
            'service_parameter' => 'hello',
        )));

        $pimple = new Pimple;

        $this->createConfigurator(false)->configure($pimple, 'config.json');

        $this->assertEquals(file_get_contents($this->cacheFile), static::CACHE_CONTENT);
    }

    protected function createConfigurator($debug = true)
    {
        $configurator = new Configurator($this->delegator, $this->resources);
        $configurator->setDebug($debug);
        $configurator->setCacheDir('/var/tmp');

        return $configurator;
    }
}
