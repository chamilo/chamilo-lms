<?php

namespace Ddeboer\DataImport\Tests\ItemConverter;

use Ddeboer\DataImport\ItemConverter\MappingItemConverter;

class MappingItemConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $input = array(
            'foo' => 'bar',
            'baz' => array(
                'another' => 'thing'
            )
        );

        $converter = new MappingItemConverter();
        $converter->addMapping('foo', 'bazinga')
            ->addMapping('baz', array('another' => 'somethingelse'));

        $output = $converter->convert($input);

        $expected = array(
            'bazinga' => 'bar',
            'baz' => array(
                'somethingelse' => 'thing'
            )
        );
        $this->assertEquals($expected, $output);
    }
}