<?php

namespace spec\SM\Extension\Twig;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SM\Factory\FactoryInterface;
use SM\StateMachine\StateMachineInterface;
use spec\SM\DummyObject;

class SMExtensionSpec extends ObjectBehavior
{
    function let(FactoryInterface $factory, StateMachineInterface $stateMachine)
    {
        $this->beConstructedWith($factory);
        $factory->get(new DummyObject(), 'simple')->willReturn($stateMachine);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('SM\Extension\Twig\SMExtension');
    }

    function it_is_a_twig_extension()
    {
        $this->shouldBeAnInstanceOf('\Twig_Extension');
    }

    function it_should_have_a_name()
    {
        $this->getName()->shouldReturn('sm');
    }

    function it_provide_sm_can_function(FactoryInterface $factory, StateMachineInterface $stateMachine)
    {
        $this->can($object = new DummyObject(), 'new', 'simple');

        $factory->get($object, 'simple')->shouldHaveBeenCalled();
        $stateMachine->can('new')->shouldHaveBeenCalled();
    }

    function it_provide_sm_getState_function(FactoryInterface $factory, StateMachineInterface $stateMachine)
    {
        $this->getState($object = new DummyObject(), 'simple');

        $factory->get($object, 'simple')->shouldHaveBeenCalled();
        $stateMachine->getState()->shouldHaveBeenCalled();
    }

    function it_provide_sm_getPossibleTransitions_function(FactoryInterface $factory, StateMachineInterface $stateMachine)
    {
        $this->getPossibleTransitions($object = new DummyObject(), 'simple');

        $factory->get($object, 'simple')->shouldHaveBeenCalled();
        $stateMachine->getPossibleTransitions()->shouldHaveBeenCalled();
    }
}
