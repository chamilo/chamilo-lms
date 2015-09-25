<?php

namespace MediaAlchemyst\Tests\Specification;

use MediaAlchemyst\Specification\Video;
use MediaAlchemyst\Specification\SpecificationInterface;

class VideoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Video
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Video;
    }

    /**
     * @covers MediaAlchemyst\Specification\Video::getType
     */
    public function testGetType()
    {
        $this->assertEquals(SpecificationInterface::TYPE_VIDEO, $this->object->getType());
    }

    /**
     * @covers MediaAlchemyst\Specification\Video::setVideoCodec
     * @covers MediaAlchemyst\Specification\Video::getVideoCodec
     */
    public function testSetVideoCodec()
    {
        $this->object->setVideoCodec('Aubergine');
        $this->assertEquals('Aubergine', $this->object->getVideoCodec());
    }

    /**
     * @covers MediaAlchemyst\Specification\Video::setDimensions
     * @covers MediaAlchemyst\Specification\Video::getWidth
     * @covers MediaAlchemyst\Specification\Video::getHeight
     */
    public function testSetDimensions()
    {
        $this->object->setDimensions(480, 220);
        $this->assertEquals(480, $this->object->getWidth());
        $this->assertEquals(220, $this->object->getHeight());
    }

    /**
     * @covers MediaAlchemyst\Specification\Video::setGOPSize
     * @covers MediaAlchemyst\Specification\Video::getGOPSize
     */
    public function testSetGOPSize()
    {
        $this->object->setGOPSize(10);
        $this->assertEquals(10, $this->object->getGOPSize());
    }

    /**
     * @covers MediaAlchemyst\Specification\Video::setFrameRate
     * @covers MediaAlchemyst\Specification\Video::getFrameRate
     */
    public function testSetFrameRate()
    {
        $this->object->setFramerate(10);
        $this->assertEquals(10, $this->object->getFramerate());
    }
}
