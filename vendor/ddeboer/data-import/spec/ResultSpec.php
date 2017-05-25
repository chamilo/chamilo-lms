<?php

namespace spec\Ddeboer\DataImport;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ResultSpec extends ObjectBehavior
{
    function let(\DateTime $startTime, \DateTime $endTime, \SplObjectStorage $exceptions, \DateInterval $elapsed)
    {
        $startTime->diff($endTime)->willReturn($elapsed);
        $exceptions->count()->willReturn(4);
        $this->beConstructedWith('name', $startTime, $endTime, 10, $exceptions);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Result');
    }

    function it_has_a_name()
    {
        $this->getName()->shouldReturn('name');
    }

    function it_has_a_start_time(\DateTime $startTime)
    {
        $this->getStartTime()->shouldReturn($startTime);
    }

    function it_has_a_end_time(\DateTime $endTime)
    {
        $this->getEndTime()->shouldReturn($endTime);
    }

    function it_has_an_elapsed_time(\DateInterval $elapsed)
    {
        $this->getElapsed()->shouldReturn($elapsed);
    }

    function it_has_an_error_count()
    {
        $this->getErrorCount()->shouldReturn(4);
    }

    function it_has_a_success_count()
    {
        $this->getSuccessCount()->shouldReturn(6);
    }

    function it_has_a_total_processed_count()
    {
        $this->getTotalProcessedCount()->shouldReturn(10);
    }

    function it_checks_if_it_has_errors()
    {
        $this->hasErrors()->shouldReturn(true);
    }

    function it_has_exceptions(\SplObjectStorage $exceptions)
    {
        $this->getExceptions()->shouldReturn($exceptions);
    }
}
