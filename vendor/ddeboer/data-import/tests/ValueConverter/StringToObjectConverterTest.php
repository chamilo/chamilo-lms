<?php

namespace Ddeboer\DataImport\ValueConverter;

use Ddeboer\DataImport\ValueConverter\StringToObjectConverter;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class StringToObjectConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $repository = $this->getMock(
            'Doctrine\\Common\\Persistence\\ObjectRepository',
            array('find', 'findAll', 'findBy', 'findOneBy', 'getClassName', 'findOneByName')
        );

        $converter = new StringToObjectConverter($repository, 'name');

        $class = new \stdClass();

        $repository->expects($this->once())
            ->method('findOneByName')
            ->with('bar')
            ->will($this->returnValue($class));

        $this->assertEquals($class, call_user_func($converter, 'bar'));
    }
}
