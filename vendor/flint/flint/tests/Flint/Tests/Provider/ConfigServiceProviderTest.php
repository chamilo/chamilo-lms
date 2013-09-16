<?php

namespace Flint\Tests\Provider;

use Flint\Application;
use Flint\Provider\ConfigServiceProvider;

class ConfigServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->application = new Application(__DIR__, true);
        $this->provider = new ConfigServiceProvider;
    }

    public function testServicesAreRegistered()
    {
        $this->provider->register($this->application);

        $this->assertInstanceOf('Symfony\Component\Config\FileLocator', $this->application['config.locator']);
        $this->assertInternalType('array', $this->application['config.paths']);
    }

    public function testConfigPathsContainsRootConfigDirectory()
    {
        $this->provider->register($this->application);
        $this->assertContains(__DIR__ . '/config', $this->application['config.paths']);
        $this->assertContains(__DIR__, $this->application['config.paths']);
    }
}
