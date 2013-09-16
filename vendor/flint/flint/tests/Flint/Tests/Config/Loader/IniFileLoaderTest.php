<?php

namespace Flint\Tests\Config\Loader;

use Flint\Config\ResourceCollection;
use Flint\Config\Loader\IniFileLoader;
use Symfony\Component\Config\FileLocator;

class IniFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $paths = array(__DIR__ . '/../../Fixtures');

        $normalizer = $this->getMock('Flint\Config\Normalizer\NormalizerInterface');
        $normalizer->expects($this->any())->method('normalize')->will($this->returnCallback(function ($args) {
            return $args;
        }));

        $this->loader = new IniFileLoader($normalizer, new FileLocator($paths), new ResourceCollection);
    }

    public function testLoads()
    {
        $this->assertEquals(array('doctrine' => array('driver' => 'mysql')), $this->loader->load('config.ini'));
    }

    public function testLoadsInheritedConfig()
    {
        $parameters = array(
            'doctrine' => array('driver' => 'pdo_pgsql', 'port' => 8080),
        );

        $this->assertEquals($parameters, $this->loader->load('inherit.ini'));
    }

    public function testSupports()
    {
        $this->assertTrue($this->loader->supports('config.ini'));
        $this->assertFalse($this->loader->supports('config.json'));
        $this->assertFalse($this->loader->supports('config.xml'));
        $this->assertFalse($this->loader->supports('config.php'));
    }
}
