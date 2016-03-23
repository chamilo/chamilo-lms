<?php

namespace PHPExiftool\Test;

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use PHPExiftool\Exiftool;

class ExiftoolTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers PHPExiftool\Exiftool::executeCommand
     */
    public function testExecuteCommand()
    {
        $exiftool = new Exiftool($this->getlogger());
        $this->assertRegExp('/\d+\.\d+/', $exiftool->executeCommand('-ver'));
    }

    /**
     * @covers PHPExiftool\Exiftool::executeCommand
     * @covers \PHPExiftool\Exception\RuntimeException
     * @expectedException \PHPExiftool\Exception\RuntimeException
     */
    public function testExecuteCommandFailed()
    {
        $exiftool = new Exiftool($this->getlogger());
        $exiftool->executeCommand('-prout');
    }

    private function getlogger()
    {
        $logger = new Logger('Tests');
        $logger->pushHandler(new NullHandler());

        return $logger;
    }
}

