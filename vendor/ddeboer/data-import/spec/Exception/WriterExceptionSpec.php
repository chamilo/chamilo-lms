<?php

namespace spec\Ddeboer\DataImport\Exception;

use PhpSpec\ObjectBehavior;

class WriterExceptionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Exception\WriterException');
    }

    function it_is_an_exception()
    {
        $this->shouldHaveType('Exception');
        $this->shouldImplement('Ddeboer\DataImport\Exception');
    }
}
