<?php

namespace Ddeboer\DataImport\ItemConverter;

class NestedMappingItemConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertSimple()
    {
        $input = array(
            'bar' => 'value',
            'simple' => array('subsimple' => 'value'),
            'simple-key' => array('foo' => 'bar'),
            'nested' => array(
                array('another' => 'thing', 'something' => 's1'),
                array('another' => 'thing2', 'something' => 's2'),
            ),
        );

        $mappings = array(
            'bar' => 'foo',
            'simple' => array('subsimple' => 'subsimple-foo'),
            'simple-key' => 'simple-key-foo',
            'nested' => array(
                'another'   => 'different_thing',
                'something' => 'else'
            )
        );

        $converter = new NestedMappingItemConverter('nested', $mappings);
        $output = $converter->convert($input);

        $expected = array(
            'foo'   => 'value',
            'simple' => array('subsimple-foo' => 'value'),
            'simple-key-foo' =>  array('foo' => 'bar'),
            'nested' => array(
                    array('different_thing' => 'thing', 'else' => 's1'),
                    array('different_thing' => 'thing2', 'else' => 's2'),
                )
        );
        $this->assertEquals($expected, $output);
    }
}