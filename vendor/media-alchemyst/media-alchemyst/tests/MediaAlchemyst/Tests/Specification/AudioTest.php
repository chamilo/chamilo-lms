<?php

namespace MediaAlchemyst\Tests\Specification;

use MediaAlchemyst\Specification\Audio;
use MediaAlchemyst\Specification\SpecificationInterface;

class AudioTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new Audio();
    }

    public function testGetType()
    {
        $this->assertEquals(SpecificationInterface::TYPE_AUDIO, $this->object->getType());
    }

    public function testSetAudioKiloBitrate()
    {
        $this->object->setAudioKiloBitrate(200);
        $this->assertEquals(200, $this->object->getAudioKiloBitrate());
    }

    public function testSetAudioCodec()
    {
        $this->object->setAudioCodec('Carlos');
        $this->assertEquals('Carlos', $this->object->getAudioCodec());
    }

    public function testSetAudioSampleRate()
    {
        $this->object->setAudioSampleRate(22050);
        $this->assertEquals(22050, $this->object->getAudioSampleRate());
    }
}
