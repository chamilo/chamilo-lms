<?php

namespace MediaAlchemyst\Tests\Specification;

use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\SpecificationInterface;

class ImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Image
     */
    protected $object;

    /**
     * @covers MediaAlchemyst\Specification\Image
     */
    protected function setUp()
    {
        $this->object = new Image();
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::getType
     */
    public function testGetType()
    {
        $this->assertEquals(SpecificationInterface::TYPE_IMAGE, $this->object->getType());
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::setImageCodec
     * @covers MediaAlchemyst\Specification\Image::getImageCodec
     */
    public function testCodec()
    {
        $this->object->setImageCodec("a_codec");
        $this->assertEquals("a_codec", $this->object->getImageCodec());
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::setDimensions
     * @covers MediaAlchemyst\Specification\Image::getWidth
     * @covers MediaAlchemyst\Specification\Image::getHeight
     */
    public function testSetDimensions()
    {
        $this->object->setDimensions(320, 240);
        $this->assertEquals(320, $this->object->getWidth());
        $this->assertEquals(240, $this->object->getHeight());
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::setResizeMode
     * @covers MediaAlchemyst\Specification\Image::getResizeMode
     */
    public function testSetResizeMode()
    {
        $this->assertEquals(Image::RESIZE_MODE_INBOUND_FIXEDRATIO, $this->object->getResizeMode());
        $this->object->setResizeMode(Image::RESIZE_MODE_OUTBOUND);
        $this->assertEquals(Image::RESIZE_MODE_OUTBOUND, $this->object->getResizeMode());
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::setResizeMode
     * @covers MediaAlchemyst\Exception\InvalidArgumentException
     * @expectedException MediaAlchemyst\Exception\InvalidArgumentException
     */
    public function testSetResizeModeFail()
    {
        $this->object->setResizeMode('+Agauche');
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::setRotationAngle
     * @covers MediaAlchemyst\Specification\Image::getRotationAngle
     */
    public function testSetRotationAngle()
    {
        $this->object->setRotationAngle(90);
        $this->assertEquals(90, $this->object->getRotationAngle());
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::setQuality
     * @covers MediaAlchemyst\Specification\Image::getQuality
     */
    public function testSetQuality()
    {
        $this->object->setQuality(60);
        $this->assertEquals(60, $this->object->getQuality());
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::setResolution
     * @covers MediaAlchemyst\Specification\Image::getResolutionUnit
     * @covers MediaAlchemyst\Specification\Image::getResolutionX
     * @covers MediaAlchemyst\Specification\Image::getResolutionY
     */
    public function testSetResolution()
    {
        $this->object->setResolution(60, 80, Image::RESOLUTION_PIXELPERINCH);
        $this->assertEquals(60, $this->object->getResolutionX());
        $this->assertEquals(80, $this->object->getResolutionY());
        $this->assertEquals(Image::RESOLUTION_PIXELPERINCH, $this->object->getResolutionUnit());

        $this->object->setResolution(70, 90, Image::RESOLUTION_PIXELPERCENTIMETER);
        $this->assertEquals(70, $this->object->getResolutionX());
        $this->assertEquals(90, $this->object->getResolutionY());
        $this->assertEquals(Image::RESOLUTION_PIXELPERCENTIMETER, $this->object->getResolutionUnit());
    }

    /**
     * @dataProvider getWrongResolutions
     * @covers MediaAlchemyst\Specification\Image::setResolution
     * @expectedException MediaAlchemyst\Exception\InvalidArgumentException
     */
    public function testSetWrongResolution($res_x, $res_y, $res_unit)
    {
        $this->object->setResolution($res_x, $res_y, $res_unit);
    }

    public function getWrongResolutions()
    {
        return array(
          array(10, 20, 'pixelparpied'),
          array(0, 20, Image::RESOLUTION_PIXELPERINCH),
          array(-5, 20, Image::RESOLUTION_PIXELPERINCH),
          array(10, 0, Image::RESOLUTION_PIXELPERINCH),
          array(10, -5, Image::RESOLUTION_PIXELPERINCH),
        );
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::setQuality
     * @expectedException MediaAlchemyst\Exception\InvalidArgumentException
     */
    public function testSetWrongQuality()
    {
        $this->object->setQuality(160);
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::setStrip
     * @covers MediaAlchemyst\Specification\Image::getStrip
     */
    public function testSetStrip()
    {
        $this->object->setStrip(true);
        $this->assertEquals(true, $this->object->getStrip());
        $this->object->setStrip(false);
        $this->assertEquals(false, $this->object->getStrip());
    }

    /**
     * @covers MediaAlchemyst\Specification\Image::setFlatten
     * @covers MediaAlchemyst\Specification\Image::isFlatten
     */
    public function testSetFlatten()
    {
        $this->object->setFlatten(true);
        $this->assertEquals(true, $this->object->isFlatten());
        $this->object->setFlatten(false);
        $this->assertEquals(false, $this->object->isFlatten());
    }
}
