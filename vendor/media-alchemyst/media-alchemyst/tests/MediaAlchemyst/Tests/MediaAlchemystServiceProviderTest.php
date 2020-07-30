<?php

namespace MediaAlchemyst\Tests;

use MediaAlchemyst\MediaAlchemystServiceProvider;
use Silex\Application;
use Symfony\Component\Process\ExecutableFinder;

class MediaAlchemystServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleUsage()
    {
        $app = new Application();
        $app->register(new MediaAlchemystServiceProvider());

        $this->assertInstanceOf('MediaAlchemyst\Alchemyst', $app['media-alchemyst']);
    }

    public function testConfiguredUsage()
    {
        $executableFinder = new ExecutableFinder();
        $php = $executableFinder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find mandatory PHP executable');
        }

        $app = new Application();
        $app->register(new MediaAlchemystServiceProvider(), array(
            'media-alchemyst.configuration' => array(
                'ffmpeg.threads'               => 42,
                'ffmpeg.ffmpeg.timeout'        => 42,
                'ffmpeg.ffprobe.timeout'       => 42,
                'ffmpeg.ffmpeg.binaries'       => $php,
                'ffmpeg.ffprobe.binaries'      => $php,
                'imagine.driver'               => 'gd',
                'gs.timeout'                   => 42,
                'gs.binaries'                  => $php,
                'mp4box.timeout'               => 42,
                'mp4box.binaries'              => $php,
                'swftools.timeout'             => 42,
                'swftools.pdf2swf.binaries'    => $php,
                'swftools.swfrender.binaries'  => $php,
                'swftools.swfextract.binaries' => $php,
                'unoconv.binaries'             => $php,
                'unoconv.timeout'              => 42,
            )
        ));

        $drivers = $app['media-alchemyst']->getDrivers();

        $this->assertEquals($php, $drivers['ffmpeg.ffmpeg']->getFFMpegDriver()->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $drivers['ffmpeg.ffprobe']->getFFProbeDriver()->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $drivers['ghostscript.transcoder']->getProcessBuilderFactory()->getBinary());
        $this->assertInstanceOf('Imagine\Gd\Imagine', $drivers['imagine']);
        $this->assertEquals($php, $drivers['swftools.driver-container']['pdf2swf']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $drivers['swftools.driver-container']['swfextract']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $drivers['swftools.driver-container']['swfrender']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $drivers['unoconv']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $drivers['mp4box']->getProcessBuilderFactory()->getBinary());

        $this->assertEquals(42, $drivers['ffmpeg.ffmpeg']->getFFMpegDriver()->getConfiguration()->get('ffmpeg.threads'));
        $this->assertEquals(42, $drivers['ffmpeg.ffmpeg']->getFFMpegDriver()->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['ffmpeg.ffprobe']->getFFProbeDriver()->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['ghostscript.transcoder']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['swftools.driver-container']['pdf2swf']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['swftools.driver-container']['swfextract']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['swftools.driver-container']['swfrender']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['unoconv']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['mp4box']->getProcessBuilderFactory()->getTimeout());
    }
}
