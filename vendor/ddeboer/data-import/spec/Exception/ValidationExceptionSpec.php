<?php

namespace spec\Ddeboer\DataImport\Exception;

use PhpSpec\ObjectBehavior;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationExceptionSpec extends ObjectBehavior
{
    function let(ConstraintViolationListInterface $list)
    {
        $this->beConstructedWith($list, 1);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Exception\ValidationException');
    }

    function it_is_an_exception()
    {
        $this->shouldHaveType('Exception');
        $this->shouldImplement('Ddeboer\DataImport\Exception');
    }

    function it_has_a_list_of_violations(ConstraintViolationListInterface $list)
    {
        $this->getViolations()->shouldReturn($list);
    }

    function it_has_a_line_number()
    {
        $this->getLineNumber()->shouldReturn(1);
    }
}
