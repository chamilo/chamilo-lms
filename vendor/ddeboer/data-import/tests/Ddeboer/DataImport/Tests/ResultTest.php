<?php

namespace Ddeboer\DataImport\Tests;

use Ddeboer\DataImport\Result;

/**
 * Tests For Workflow Result
 *
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testResultName()
    {
        $result = new Result('export', new \DateTime, new \DateTime, 10);
        $this->assertSame('export', $result->getName());
    }

    public function testResultCounts()
    {
        $result = new Result('export', new \DateTime, new \DateTime, 10);
        $this->assertSame(10, $result->getTotalProcessedCount());
        $this->assertSame(10, $result->getSuccessCount());
        $this->assertSame(0, $result->getErrorCount());
        $result = new Result('export', new \DateTime, new \DateTime, 10, array(new \Exception, new \Exception));
        $this->assertSame(10, $result->getTotalProcessedCount());
        $this->assertSame(8, $result->getSuccessCount());
        $this->assertSame(2, $result->getErrorCount());

    }

    public function testDates()
    {
        $startDate  = new \DateTime("22-07-2014 22:00");
        $endDate    = new \DateTime("22-07-2014 23:30");

        $result     = new Result('export', $startDate, $endDate, 10);

        $this->assertSame($startDate, $result->getStartTime());
        $this->assertSame($endDate, $result->getEndTime());
        $this->assertInstanceOf('DateInterval', $result->getElapsed());
    }

    public function testHasErrorsReturnsTrueIfAnyExceptions()
    {
        $result = new Result('export', new \DateTime, new \DateTime, 10, array(new \Exception, new \Exception));
        $this->assertTrue($result->hasErrors());
    }

    public function testHasErrorsReturnsFalseIfNoExceptions()
    {
        $result = new Result('export', new \DateTime, new \DateTime, 10);
        $this->assertFalse($result->hasErrors());
    }

    public function testGetExceptions()
    {
        $exceptions = array(new \Exception, new \Exception);
        $result = new Result('export', new \DateTime, new \DateTime, 10, $exceptions);
        $this->assertSame($exceptions, $result->getExceptions());
    }
}
