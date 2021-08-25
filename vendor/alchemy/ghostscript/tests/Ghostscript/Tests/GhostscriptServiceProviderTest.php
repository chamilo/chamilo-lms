<?php

namespace Ghostscript\Tests;

use Ghostscript\GhostscriptServiceProvider;
use Silex\Application;
use Symfony\Component\Process\ExecutableFinder;

class GhostscriptServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application;
        $app->register(new GhostscriptServiceProvider());

        $this->assertInstanceOf('\\Ghostscript\\Transcoder', $app['ghostscript.transcoder']);
    }

    public function testRegisterWithCustomTimeout()
    {
        $app = new Application;
        $app->register(new GhostscriptServiceProvider(), array(
            'ghostscript.configuration' => array(
                'timeout' => 42
            ),
        ));

        $this->assertEquals(42, $app['ghostscript.transcoder']->getProcessBuilderfactory()->getTimeout());
    }

    public function testRegisterWithCustomBinary()
    {
        $finder = new ExecutableFinder();
        $MP4Box = $finder->find('MP4Box');

        if (null === $MP4Box) {
            $this->markTestSkipped('Unable to detect MP4Box, required for this test');
        }

        $app = new Application;
        $app->register(new GhostscriptServiceProvider(), array(
            'ghostscript.configuration' => array(
                'gs.binaries' => $MP4Box
            ),
        ));

        $this->assertEquals($MP4Box, $app['ghostscript.transcoder']->getProcessBuilderfactory()->getBinary());
    }

    public function testRegisterWithCustomLogger()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $app = new Application;
        $app->register(new GhostscriptServiceProvider(), array(
            'ghostscript.logger' => $logger,
        ));

        $this->assertEquals($logger, $app['ghostscript.transcoder']->getProcessRunner()->getLogger());
    }
}
