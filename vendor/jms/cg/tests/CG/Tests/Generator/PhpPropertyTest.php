<?php

namespace CG\Tests\Generator;

use CG\Generator\PhpProperty;

class PhpPropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetDefaultValue()
    {
        $prop = new PhpProperty();

        $this->assertNull($prop->getDefaultValue());
        $this->assertFalse($prop->hasDefaultValue());
        $this->assertSame($prop, $prop->setDefaultValue('foo'));
        $this->assertEquals('foo', $prop->getDefaultValue());
        $this->assertTrue($prop->hasDefaultValue());
        $this->assertSame($prop, $prop->unsetDefaultValue());
        $this->assertNull($prop->getDefaultValue());
        $this->assertFalse($prop->hasDefaultValue());
    }
}