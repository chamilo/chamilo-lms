<?php

namespace CG\Tests\Generator;

use CG\Generator\PhpParameter;

class PhpParameterTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetName()
    {
        $param = new PhpParameter();

        $this->assertNull($param->getName());
        $this->assertSame($param, $param->setName('foo'));
        $this->assertEquals('foo', $param->getName());
    }

    public function testSetGetDefaultValue()
    {
        $param = new PhpParameter();

        $this->assertNull($param->getDefaultValue());
        $this->assertFalse($param->hasDefaultValue());
        $this->assertSame($param, $param->setDefaultValue('foo'));
        $this->assertEquals('foo', $param->getDefaultValue());
        $this->assertTrue($param->hasDefaultValue());
        $this->assertSame($param, $param->unsetDefaultValue());
        $this->assertNull($param->getDefaultValue());
        $this->assertFalse($param->hasDefaultValue());
    }

    public function testSetIsPassedByReference()
    {
        $param = new PhpParameter();

        $this->assertFalse($param->isPassedByReference());
        $this->assertSame($param, $param->setPassedByReference(true));
        $this->assertTrue($param->isPassedByReference());
        $this->assertSame($param, $param->setPassedByReference(false));
        $this->assertFalse($param->isPassedByReference());
    }

    public function testSetGetType()
    {
        $param = new PhpParameter();

        $this->assertNull($param->getType());
        $this->assertSame($param, $param->setType('array'));
        $this->assertEquals('array', $param->getType());
    }
}