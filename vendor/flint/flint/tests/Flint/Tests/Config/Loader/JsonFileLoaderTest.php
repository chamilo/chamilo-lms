<?php

namespace Flint\Tests\Config\Loader;

use Flint\Config\ResourceCollection;
use Flint\Config\Loader\JsonFileLoader;
use Symfony\Component\Config\FileLocator;

class JsonFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $paths = array(__DIR__ . '/../../Fixtures');

        $normalizer = $this->getMock('Flint\Config\Normalizer\NormalizerInterface');
        $normalizer->expects($this->any())->method('normalize')->will($this->returnCallback(function ($args) {
            return $args;
        }));

        $this->loader = new JsonFileLoader($normalizer, new FileLocator($paths), new ResourceCollection);
    }

    public function testItLoadsAsJsonFile()
    {
        $this->assertEquals(array('base_parameter' => 'hello'), $this->loader->load('base.json'));
    }

    public function testItLoadsInheritedJsonFiles()
    {
        $this->assertEquals(array('base_parameter' => 'hello', 'service_parameter' => 'hello'), $this->loader->load('config.json'));
    }

    public function testItSupportsJson()
    {
        $this->assertTrue($this->loader->supports('config.json'));
        $this->assertFalse($this->loader->supports('config.ini'));
        $this->assertFalse($this->loader->supports('config.xml'));
        $this->assertFalse($this->loader->supports('config.php'));
    }
}
