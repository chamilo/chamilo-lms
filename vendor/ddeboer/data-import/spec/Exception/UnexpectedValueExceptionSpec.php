<?php

namespace spec\Ddeboer\DataImport\Exception;

use PhpSpec\ObjectBehavior;

class UnexpectedValueExceptionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Exception\UnexpectedValueException');
    }

    function it_is_an_exception()
    {
        $this->shouldImplement('Ddeboer\DataImport\Exception');
    }
}
