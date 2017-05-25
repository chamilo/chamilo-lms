<?php

namespace spec\Ddeboer\DataImport\Exception;

use PhpSpec\ObjectBehavior;

class UnexpectedTypeExceptionSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith(123, 'string');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Exception\UnexpectedTypeException');
    }

    function it_is_an_unexpected_value_exception()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Exception\UnexpectedValueException');
    }

    function it_has_a_message_with_scalar_type()
    {
        $this->getMessage()->shouldReturn('Expected argument of type "string", "integer" given');
    }

    function it_has_a_message_with_object_type()
    {
        $this->beConstructedWith(new \stdClass, 'string');

        $this->getMessage()->shouldReturn('Expected argument of type "string", "stdClass" given');
    }
}
