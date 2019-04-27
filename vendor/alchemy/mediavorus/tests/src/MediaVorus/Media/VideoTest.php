<?php

namespace MediaVorus\Media;

use MediaVorus\MediaVorus;
use MediaVorus\Media\MediaInterface;

class VideoTest extends MediaTestCase
{
    /**
     * @var Video
     */
    protected $object;
    protected $mediavorus;

    public function setUp()
    {
        parent::setUp();
        $this->mediavorus = MediaVorus::create();
        $this->file = __DIR__ . '/../../../files/videoFlashed.MOV';
        $this->object = $this->mediavorus->guess($this->file);
    }

    /**
     * @covers \MediaVorus\Media\Video::getType
     */
    public function testGetType()
    {
        $this->assertEquals(MediaInterface::TYPE_VIDEO, $this->object->getType());
    }

    /**
     * @covers \MediaVorus\Media\Video::getWidth
     */
    public function testGetWidth()
    {
        $this->assertTrue(is_int($this->object->getWidth()));
        $this->assertEquals(568, $this->object->getWidth());
    }

    /**
     * @covers \MediaVorus\Media\Video::getHeight
     */
    public function testGetHeight()
    {
        $this->assertTrue(is_int($this->object->getHeight()));
        $this->assertEquals(320, $this->object->getHeight());
    }

    /**
     * @covers \MediaVorus\Media\Video::getDuration
     */
    public function testGetDuration()
    {
        $this->assertTrue(is_float($this->object->getDuration()));
        $this->assertEquals(2, floor($this->object->getDuration()));
    }

    /**
     * @covers \MediaVorus\Media\Video::getFrameRate
     */
    public function testGetFrameRate()
    {
        $this->assertTrue(is_float($this->object->getFrameRate()));
        $this->assertEquals(30.0, $this->object->getFrameRate());
    }

    /**
     * @covers \MediaVorus\Media\Video::getAudioSampleRate
     */
    public function testGetAudioSampleRate()
    {
        $this->assertTrue(is_int($this->object->getAudioSampleRate()));
        $this->assertEquals(44100, $this->object->getAudioSampleRate());
    }

    /**
     * @covers \MediaVorus\Media\Video::getVideoCodec
     */
    public function testGetVideoCodec()
    {
        $this->assertEquals('H.264', $this->object->getVideoCodec());
    }

    /**
     * @covers \MediaVorus\Media\Video::getAudioCodec
     */
    public function testGetAudioCodec()
    {
        $this->assertEquals('mp4a', $this->object->getAudioCodec());
    }

    public function testSerialize()
    {
        $json = $this->getSerializer()->serialize($this->object, 'json');

        $data = json_decode($json, true);

        $this->assertArrayNotHasKey('ffprobe', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('height', $data);
        $this->assertArrayHasKey('width', $data);
        $this->assertArrayHasKey('duration', $data);
    }
}
