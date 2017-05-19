<?php

namespace spec\Ddeboer\DataImport\Exception;

use PhpSpec\ObjectBehavior;

class MappingExceptionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Exception\MappingException');
    }

    function it_is_an_exception()
    {
        $this->shouldHaveType('Exception');
        $this->shouldImplement('Ddeboer\DataImport\Exception');
    }
}
