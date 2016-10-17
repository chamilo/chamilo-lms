<?php

namespace spec\Ddeboer\DataImport\Filter;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ValidatorFilterSpec extends ObjectBehavior
{
    /**
     * @var array
     */
    protected $item1 = ['key1' => 'value1'];

    /**
     * @var array
     */
    protected $item2 = [
        'key1' => 'value1',
        'key2' => 'value2'
    ];

    function let(ValidatorInterface $validator)
    {
        $this->beConstructedWith($validator);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ddeboer\DataImport\Filter\ValidatorFilter');
    }

    function it_validates_an_item(ValidatorInterface $validator, Constraint $constraint, ConstraintViolationList $list)
    {
        $list->count()->willReturn(0);
        $validator->validate($this->item1, Argument::type('Symfony\Component\Validator\Constraints\Collection'))->willReturn($list);

        $this->add('key1', $constraint);

        $this->__invoke($this->item1);
    }

    function it_validates_an_item_non_strictly(ValidatorInterface $validator, Constraint $constraint, ConstraintViolationList $list)
    {
        $list->count()->willReturn(0);
        $validator->validate($this->item1, Argument::type('Symfony\Component\Validator\Constraints\Collection'))->willReturn($list);

        $this->setStrict(false);
        $this->add('key1', $constraint);

        $this->__invoke($this->item1);
        $this->__invoke($this->item2);
    }

    function it_validates_an_item_and_the_validation_fails(ValidatorInterface $validator, Constraint $constraint, ConstraintViolationList $list)
    {
        $list->count()->willReturn(1);
        $validator->validate($this->item1, Argument::type('Symfony\Component\Validator\Constraints\Collection'))->willReturn($list);

        $this->add('key1', $constraint);

        $this->__invoke($this->item1);

        $this->getViolations()->shouldReturn([1 => $list]);
    }

    function it_validates_an_item_and_the_validation_fails_with_exception(ValidatorInterface $validator, Constraint $constraint, ConstraintViolationList $list)
    {
        $list->count()->willReturn(1);
        $validator->validate($this->item1, Argument::type('Symfony\Component\Validator\Constraints\Collection'))->willReturn($list);

        $this->throwExceptions(true);
        $this->add('key1', $constraint);

        $this->shouldThrow('Ddeboer\DataImport\Exception\ValidationException')->during__invoke($this->item1);

        $this->getViolations()->shouldReturn([1 => $list]);
    }
}
