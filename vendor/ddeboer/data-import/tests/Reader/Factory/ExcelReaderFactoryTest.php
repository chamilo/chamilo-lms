<?php

namespace Ddeboer\DataImport\Tests\Reader\Factory;

use Ddeboer\DataImport\Reader\Factory\DoctrineReaderFactory;

class ExcelReaderFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetReader()
    {
        $om = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')->getMock();
        $factory = new DoctrineReaderFactory($om);
        $reader = $factory->getReader('Some:Object');
        $this->assertInstanceOf('\Ddeboer\DataImport\Reader\DoctrineReader', $reader);
    }
}