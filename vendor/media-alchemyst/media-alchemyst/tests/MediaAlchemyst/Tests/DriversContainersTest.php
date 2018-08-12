<?php

namespace MediaAlchemyst\Tests;

use MediaAlchemyst\DriversContainer;
use Symfony\Component\Process\ExecutableFinder;

class DriversContainersTest extends \PHPUnit_Framework_TestCase
{
    public function testDrivers()
    {
        $object = new DriversContainer();
        $this->assertInstanceOf('\\FFMpeg\\FFMpeg', $object['ffmpeg.ffmpeg']);
        $this->assertInstanceOf('\\Imagine\\Image\\ImagineInterface', $object['imagine']);
        $this->assertInstanceOf('\\SwfTools\\Processor\\FlashFile', $object['swftools.flash-file']);
        $this->assertInstanceOf('\\SwfTools\\Processor\\PDFFile', $object['swftools.pdf-file']);

        $executableFinder = new ExecutableFinder();
        if ($executableFinder->find('unoconv')) {
            $this->assertInstanceOf('\\Unoconv\\Unoconv', $object['unoconv']);
        }

        $this->assertInstanceOf('\\PHPExiftool\\PreviewExtractor', $object['exiftool.preview-extractor']);
        $this->assertInstanceOf('\\MP4Box\\MP4Box', $object['mp4box']);
    }

    public function testCustomizedDrivers()
    {
        $object = new DriversContainer();

        $executableFinder = new ExecutableFinder();
        $php = $executableFinder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find mandatory PHP executable');
        }

        $object['configuration'] = array(
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
        );

        $this->assertEquals($php, $object['ffmpeg.ffmpeg']->getFFMpegDriver()->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $object['ffmpeg.ffprobe']->getFFProbeDriver()->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $object['ghostscript.transcoder']->getProcessBuilderFactory()->getBinary());
        $this->assertInstanceOf('Imagine\Gd\Imagine', $object['imagine']);
        $this->assertEquals($php, $object['swftools.driver-container']['pdf2swf']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $object['swftools.driver-container']['swfextract']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $object['swftools.driver-container']['swfrender']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $object['unoconv']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $object['mp4box']->getProcessBuilderFactory()->getBinary());

        $this->assertEquals(42, $object['ffmpeg.ffmpeg']->getFFMpegDriver()->getConfiguration()->get('ffmpeg.threads'));
        $this->assertEquals(42, $object['ffmpeg.ffmpeg']->getFFMpegDriver()->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['ffmpeg.ffprobe']->getFFProbeDriver()->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['ghostscript.transcoder']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['swftools.driver-container']['pdf2swf']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['swftools.driver-container']['swfextract']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['swftools.driver-container']['swfrender']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['unoconv']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['mp4box']->getProcessBuilderFactory()->getTimeout());
    }

    /**
     * @dataProvider provideKeysToTest
     */
    public function testInvalidDrivers($key1)
    {
        $object = new DriversContainer();
        $object['configuration'] = array(
            'ffmpeg.ffmpeg.binaries'       => '/path/to/nowhere',
            'ffmpeg.ffprobe.binaries'      => '/path/to/nowhere',
            'gs.binaries'                  => '/path/to/nowhere',
            'mp4box.binaries'              => '/path/to/nowhere',
            'swftools.pdf2swf.binaries'    => '/path/to/nowhere',
            'swftools.swfrender.binaries'  => '/path/to/nowhere',
            'swftools.swfextract.binaries' => '/path/to/nowhere',
            'unoconv.binaries'             => '/path/to/nowhere',
        );

        $this->setExpectedException('MediaAlchemyst\Exception\RuntimeException');
        $object[$key1];
    }

    public function provideKeysToTest()
    {
        return array(
            array('ffmpeg.ffmpeg'),
            array('ffmpeg.ffprobe'),
            array('ghostscript.transcoder'),
            array('unoconv'),
            array('mp4box'),
        );
    }
}
