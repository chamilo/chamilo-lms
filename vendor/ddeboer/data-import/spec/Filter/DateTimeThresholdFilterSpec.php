<?php

namespace spec\Ddeboer\DataImport\Filter;

use Ddeboer\DataImport\ValueConverter\DateTimeValueConverter;
use PhpSpec\ObjectBehavior;

class DateTimeThresholdFilterSpec extends ObjectBehavior
{
    function let(DateTimeValueConverter $valueConverter)
    {
        $this->beConstructedWith($valueConverter);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Filter\DateTimeThresholdFilter');
    }

    function it_throws_an_exception_when_no_threshold_is_set()
    {
        $this->shouldThrow('LogicException')->during__invoke([]);
    }

    function it_accepts_a_threshold(\DateTime $dateTime)
    {
        $this->setThreshold($dateTime);
    }

    function it_filters_an_item_based_on_a_time_column(DateTimeValueConverter $valueConverter)
    {
        $item = [
            'updated_at' => '1970-01-01'
        ];

        $valueConverter->__invoke($item['updated_at'])->willReturn(new \DateTime('1970-01-01'));
        $this->beConstructedWith($valueConverter, new \DateTime());

        $this->__invoke($item)->shouldReturn(false);
    }
}
