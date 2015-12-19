<?php

namespace Ddeboer\DataImport\Tests\ValueConverter;

use Ddeboer\DataImport\ValueConverter\ArrayValueConverterMap;
use Ddeboer\DataImport\ValueConverter\CallbackValueConverter;

/**
 * @author Christoph Rosse <christoph@rosse.at>
 */
class ArrayValueConverterMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testConvertWithNoArrayArgument()
    {
        $converter = new ArrayValueConverterMap(array('foo' => new CallbackValueConverter(function($input) {return $input;})));
        $converter->convert('foo');
    }

    public function testConvertWithMultipleFields()
    {
        $data = array(
            array(
                'foo' => 'test',
                'bar' => 'test'
            ),
            array(
                'foo' => 'test2',
                'bar' => 'test2'
            ),
        );

        $addBarConverter = new CallbackValueConverter(function($input) { return 'bar'.$input; });
        $addBazConverter = new CallbackValueConverter(function($input) { return 'baz'.$input; });

        $converter = new ArrayValueConverterMap(
            array(
                'foo' => array($addBarConverter),
                'bar' => array($addBazConverter, $addBarConverter),
            )
        );

        $data = $converter->convert($data);

        $this->assertEquals('bartest', $data[0]['foo']);
        $this->assertEquals('barbaztest', $data[0]['bar']);

        $this->assertEquals('bartest2', $data[1]['foo']);
        $this->assertEquals('barbaztest2', $data[1]['bar']);
    }
}
