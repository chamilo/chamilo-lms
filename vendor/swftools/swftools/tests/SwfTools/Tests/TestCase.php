<?php

namespace SwfTools\Tests;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function createLoggerMock()
    {
        return $this
            ->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
