<?php

use Fhaculty\Graph\Attribute\AttributeBagReference;

class AttributeBagReferenceTest extends TestCase
{
    public function testEmpty()
    {
        $attributes = array();

        $bag = new AttributeBagReference($attributes);

        $this->assertNull($bag->getAttribute('unknown'));
        $this->assertEquals('default', $bag->getAttribute('unknown', 'default'));

        $this->assertEquals(array(), $bag->getAttributes());

        $this->assertSame($bag, $bag->getAttributeBag());
    }

    public function testSome()
    {
        $attributes = array(
            'true' => true,
            'two' => 2,
        );

        $bag = new AttributeBagReference($attributes);

        $this->assertSame(true, $bag->getAttribute('true'));
        $this->assertSame(2, $bag->getAttribute('two'));
        $this->assertEquals(array('true' => true, 'two' => 2), $bag->getAttributes());

        $bag->setAttribute('float', '1.2');
        $bag->setAttributes(array('two' => 'two', 'three' => 3));

        $expected = array('true' => true, 'two' => 'two', 'float' => 1.2, 'three' => 3);
        $this->assertEquals($expected, $bag->getAttributes());

        $this->assertEquals($expected, $attributes);
    }
}
