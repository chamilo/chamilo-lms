<?php

namespace Neutron\Silex\Provider\Tests;

use Neutron\Silex\Provider\FilesystemServiceProvider;
use Silex\Application;

class FilesystemServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application();
        $app->register(new FilesystemServiceProvider());

        $this->assertInstanceOf('Symfony\\Component\\Filesystem\\Filesystem', $app['filesystem']);
    }
}
