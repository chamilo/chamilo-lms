<?php

namespace MP4Box\Tests;

use Silex\Application;
use MP4Box\MP4BoxServiceProvider;

class MP4BoxServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function getApplication()
    {
        return new Application();
    }

    public function testInit()
    {
        $app = $this->getApplication();
        $app->register(new MP4BoxServiceProvider());

        $this->assertInstanceOf('\\MP4Box\\MP4Box', $app['mp4box']);
    }

    /**
     * @expectedException Alchemy\BinaryDriver\Exception\ExecutableNotFoundException
     */
    public function testInitFailOnBinary()
    {
        $app = $this->getApplication();
        $app->register(new MP4BoxServiceProvider(), array(
            'mp4box.configuration' => array(
                'mp4box.binaries' => 'no/binary/here'
            )
        ));

        $app['mp4box'];
    }

    public function testInitCustomLogger()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $app = $this->getApplication();
        $app->register(new MP4BoxServiceProvider(), array(
            'mp4box.logger' => $logger
        ));

        $this->assertInstanceOf('\\MP4Box\\MP4Box', $app['mp4box']);
        $this->assertEquals($logger, $app['mp4box']->getProcessRunner()->getLogger());
    }

    public function testInitCustomTimeout()
    {
        $app = $this->getApplication();
        $app->register(new MP4BoxServiceProvider(), array(
            'mp4box.configuration' => array(
                'timeout' => 128
            )
        ));

        $this->assertEquals(128, $app['mp4box']->getProcessBuilderFactory()->getTimeout());
    }
}
