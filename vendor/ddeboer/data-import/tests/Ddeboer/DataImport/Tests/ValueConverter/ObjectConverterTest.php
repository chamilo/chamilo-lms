<?php

namespace Ddeboer\DataImport\Tests\ValueConverter;

use Ddeboer\DataImport\ValueConverter\ObjectConverter;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz
 */
class ObjectConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAndSetPropertyPath()
    {
        $converter = new ObjectConverter();
        $this->assertNull($converter->getPropertyPath());

        $converter->setPropertyPath('foo.bar');
        $this->assertEquals('foo.bar', $converter->getPropertyPath());
    }

    public function testConvertWithToString()
    {
        $converter = new ObjectConverter();
        $object = new ToStringDummy();

        $this->assertEquals('foo', $converter->convert($object));
    }

    public function testConvertWithPropertyPath()
    {
        $converter = new ObjectConverter('foo');
        $object = new Dummy();

        $this->assertEquals('bar', $converter->convert($object));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testConvertAObjectWithoutToString()
    {
        $converter = new ObjectConverter;
        $converter->convert(new Dummy());
    }

    /**
     * @expectedException Ddeboer\DataImport\Exception\UnexpectedTypeException
     */
    public function testConvetANonObject()
    {
        $converter = new ObjectConverter();
        $converter->convert('foo');
    }
}

class Dummy
{
    public $foo = 'bar';
}

class ToStringDummy
{
    public function __toString()
    {
        return 'foo';
    }
}
