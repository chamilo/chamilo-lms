<?php

namespace SwfTools\Tests;

use SwfTools\EmbeddedObject;

class EmbeddedObjectTest extends TestCase
{

    /**
     *
     * @var EmbeddedObject
     */
    protected $object;
    protected $option = 'G';
    protected $type = 'JPEG';
    protected $id = '24';

    /**
     * @covers SwfTools\EmbeddedObject::__construct
     */
    protected function setUp()
    {
        $this->object = new EmbeddedObject($this->option, $this->type, $this->id);
    }

    /**
     * @covers SwfTools\EmbeddedObject::getOption
     */
    public function testGetOption()
    {
        $this->assertEquals($this->option, $this->object->getOption());
    }

    /**
     * @covers SwfTools\EmbeddedObject::getType
     */
    public function testGetType()
    {
        $this->assertEquals($this->type, $this->object->getType());
    }

    /**
     * @covers SwfTools\EmbeddedObject::getId
     */
    public function testGetId()
    {
        $this->assertEquals($this->id, $this->object->getId());
    }

    /**
     * @covers SwfTools\EmbeddedObject::detectType
     */
    public function testDetectType()
    {
       $this->assertEquals(EmbeddedObject::TYPE_JPEG, EmbeddedObject::detectType('JPEGs'));
       $this->assertEquals(EmbeddedObject::TYPE_JPEG, EmbeddedObject::detectType('JPEG'));
       $this->assertEquals(EmbeddedObject::TYPE_PNG, EmbeddedObject::detectType('PNG'));
       $this->assertEquals(EmbeddedObject::TYPE_PNG, EmbeddedObject::detectType('PNGs'));
       $this->assertEquals(EmbeddedObject::TYPE_MOVIECLIP, EmbeddedObject::detectType('MovieClip'));
       $this->assertEquals(EmbeddedObject::TYPE_MOVIECLIP, EmbeddedObject::detectType('MovieClip'));
       $this->assertEquals(EmbeddedObject::TYPE_FRAME, EmbeddedObject::detectType('Frame'));
       $this->assertEquals(EmbeddedObject::TYPE_FRAME, EmbeddedObject::detectType('Frames'));
       $this->assertEquals(EmbeddedObject::TYPE_SOUND, EmbeddedObject::detectType('Sound'));
       $this->assertEquals(EmbeddedObject::TYPE_SOUND, EmbeddedObject::detectType('Sound'));
       $this->assertEquals(EmbeddedObject::TYPE_SHAPE, EmbeddedObject::detectType('Shape'));
       $this->assertEquals(EmbeddedObject::TYPE_SHAPE, EmbeddedObject::detectType('Shapes'));

       $this->assertNull(EmbeddedObject::detectType('Unknown'));
    }

}
