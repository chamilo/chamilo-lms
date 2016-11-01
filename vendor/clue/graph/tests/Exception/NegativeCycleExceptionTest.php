<?php

use Fhaculty\Graph\Walk;
use Fhaculty\Graph\Exception\NegativeCycleException;

class NegativeCycleExceptionTest extends TestCase
{
    public function testConstructor()
    {
        $cycle = $this->getMockBuilder('Fhaculty\Graph\Walk')
                      ->disableOriginalConstructor()
                      ->getMock();

        $exception = new NegativeCycleException('test', 0, null, $cycle);

        $this->assertEquals('test', $exception->getMessage());
        $this->assertEquals($cycle, $exception->getCycle());
    }
}
