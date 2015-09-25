<?php

namespace PHPExiftool\Test;

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use PHPExiftool\InformationDumper;
use PHPExiftool\Exiftool;

class InformationDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InformationDumper
     */
    protected $object;

    protected function setUp()
    {
        $logger = new Logger('Tests');
        $logger->pushHandler(new NullHandler());

        $this->object = new InformationDumper(new Exiftool($logger));
    }

    /**
     * @covers PHPExiftool\InformationDumper::listDatas
     */
    public function testListDatas()
    {
        $this->object->listDatas();
    }

    /**
     * @covers PHPExiftool\InformationDumper::listDatas
     * @covers \PHPExiftool\Exception\InvalidArgumentException
     * @expectedException \PHPExiftool\Exception\InvalidArgumentException
     */
    public function testListDatasInvalidType()
    {
        $this->object->listDatas('Scrooge');
    }
}
