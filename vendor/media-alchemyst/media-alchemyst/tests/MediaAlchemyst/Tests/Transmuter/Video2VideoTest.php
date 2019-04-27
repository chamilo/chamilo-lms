<?php

namespace MediaAlchemyst\Tests\Transmuter;

use MediaAlchemyst\Transmuter\Video2Video;
use MediaAlchemyst\Tests\AbstractAlchemystTester;
use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Tests\Specification\UnknownSpecs;
use MediaAlchemyst\Specification\Video;

class Video2VideoTest extends AbstractAlchemystTester
{
    /**
     * @var Video2Video
     */
    protected $object;
    protected $specs;
    protected $source;
    protected $dest;
    protected $mediavorus;

    protected function setUp()
    {
        $this->object = new Video2Video(new DriversContainer(), $this->getFsManager());

        $this->specs = new Video();
        $this->specs->setDimensions(320, 240);
        $this->specs->setResizeMode(Video::RESIZE_MODE_FIT);

        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/Test.ogv');
        $this->dest = __DIR__ . '/../../../files/output_video.webm';
    }

    protected function tearDown()
    {
        if (file_exists($this->dest) && is_writable($this->dest)) {
            unlink($this->dest);
        }
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Video::execute
     */
    public function testExecute()
    {
        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('video/webm', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
        $this->assertEquals(320, $mediaDest->getWidth());
        $this->assertEquals(240, $mediaDest->getHeight());
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Video::execute
     */
    public function testExecuteInset()
    {
        $this->specs->setDimensions(320, 240);
        $this->specs->setResizeMode(Video::RESIZE_MODE_INSET);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('video/webm', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
        $this->assertLessThanOrEqual(320, $mediaDest->getWidth());
        $this->assertLessThanOrEqual(240, $mediaDest->getHeight());
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Video::execute
     */
    public function testExecuteMP4()
    {
        $this->dest = __DIR__ . '/../../../files/output_video.mp4';

        $this->specs->setAudioCodec('libmp3lame');
        $this->specs->setVideoCodec('libx264');
        $this->specs->setAudioSampleRate(44100);
        $this->specs->setKiloBitrate(1000);
        $this->specs->setGOPSize(10);
        $this->specs->setFramerate(5);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('video/mp4', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
        $this->assertEquals(320, $mediaDest->getWidth());
        $this->assertEquals(240, $mediaDest->getHeight());
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Video::execute
     */
    public function testExecuteWithOptions()
    {
        $this->specs->setAudioCodec('libvorbis');
        $this->specs->setVideoCodec('libvpx');
        $this->specs->setAudioSampleRate(44100);
        $this->specs->setKiloBitrate(1000);
        $this->specs->setGOPSize(10);
        $this->specs->setFramerate(5);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('video/webm', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
        $this->assertEquals(320, $mediaDest->getWidth());
        $this->assertEquals(240, $mediaDest->getHeight());
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Video::execute
     * @covers MediaAlchemyst\Exception\SpecNotSupportedException
     * @expectedException MediaAlchemyst\Exception\SpecNotSupportedException
     */
    public function testExecuteWithBasSpecs()
    {
        $this->object->execute(new UnknownSpecs(), $this->source, $this->dest);
    }

    /**
     * @dataProvider getFormats
     * @covers MediaAlchemyst\Transmuter\Video2Video::getFormatFromFileType
     */
    public function testGetFormatFromFileType($file, $instance)
    {
        $Object = new Video2VideoExtended();
        $this->assertInstanceOf($instance, $Object->testgetFormatFromFileType($file, 200, 200));
    }

    public function getFormats()
    {
        return array(
          array('file.ogv', '\\FFMpeg\\Format\\Video\\Ogg'),
          array('file.mp4', '\\FFMpeg\\Format\\Video\\X264'),
          array('file.webm', '\\FFMpeg\\Format\\Video\\WebM'),
        );
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Video::getFormatFromFileType
     * @covers MediaAlchemyst\Exception\FormatNotSupportedException
     * @expectedException MediaAlchemyst\Exception\FormatNotSupportedException
     */
    public function testGetFormatFromWrongFileType()
    {
        $Object = new Video2VideoExtended();

        $Object->testgetFormatFromFileType('out.jpg', 200, 200);
    }
}

class Video2VideoExtended extends Video2Video
{
    public function __construct()
    {

    }

    public function testgetFormatFromFileType($dest, $width, $height)
    {
        return parent::getFormatFromFileType($dest, $width, $height);
    }
}
