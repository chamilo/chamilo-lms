<?php

namespace MediaVorus;

use FFMpeg\FFMpegServiceProvider;
use PHPExiftool\PHPExiftoolServiceProvider;
use Silex\Application;

class MediaVorusServiceProvideTest extends TestCase
{
    private function getApplication()
    {
        return new Application();
    }

    public function testInitializationWithFFProbe()
    {
        $app = $this->getApplication();

        $app->register(new MediaVorusServiceProvider());
        $app->register(new PHPExiftoolServiceProvider());
        $app->register(new FFMpegServiceProvider());
        $app->boot();

        $this->assertInstanceOf('\\MediaVorus\\MediaVorus', $app['mediavorus']);
        $this->assertSame($app['exiftool.reader'], $app['mediavorus']->getReader());
        $this->assertSame($app['exiftool.writer'], $app['mediavorus']->getWriter());
        $this->assertSame($app['ffmpeg.ffprobe'], $app['mediavorus']->getFFProbe());
    }

    public function testInitializationWithoutFFProbe()
    {
        $app = $this->getApplication();

        $app->register(new MediaVorusServiceProvider());
        $app->register(new PHPExiftoolServiceProvider());
        $app->boot();

        $this->assertInstanceOf('\\MediaVorus\\MediaVorus', $app['mediavorus']);
        $this->assertSame($app['exiftool.reader'], $app['mediavorus']->getReader());
        $this->assertSame($app['exiftool.writer'], $app['mediavorus']->getWriter());
        $this->assertNull($app['mediavorus']->getFFProbe());
    }

    public function testInitializationWithNonWorkingFFProbe()
    {
        $app = $this->getApplication();

        $app->register(new MediaVorusServiceProvider());
        $app->register(new PHPExiftoolServiceProvider());
        $app->register(new FFMpegServiceProvider(), array(
            'ffmpeg.configuration' => array(
                'ffprobe.binaries' => '/path/to/nowhere',
            )
        ));
        $app->boot();

        $this->assertInstanceOf('\\MediaVorus\\MediaVorus', $app['mediavorus']);
        $this->assertSame($app['exiftool.reader'], $app['mediavorus']->getReader());
        $this->assertSame($app['exiftool.writer'], $app['mediavorus']->getWriter());
        $this->assertNull($app['mediavorus']->getFFProbe());
    }

    /**
     * @expectedException MediaVorus\Exception\RuntimeException
     * @expectedExceptionMessage MediaVorus Service Provider requires Exiftool Service Provider
     */
    public function testFailOnExiftool()
    {
        $app = $this->getApplication();

        $app->register(new MediaVorusServiceProvider());
        $app->register(new FFMpegServiceProvider());

        $app->boot();
    }
}