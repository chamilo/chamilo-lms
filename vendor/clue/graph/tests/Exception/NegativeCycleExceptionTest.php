<?php

namespace Fhaculty\Graph\Tests\Exception;

use Fhaculty\Graph\Exception\NegativeCycleException;
use Fhaculty\Graph\Tests\TestCase;

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
