<?php

namespace spec\SM\Callback;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SM\Event\TransitionEvent;
use SM\Factory\FactoryInterface;
use SM\StateMachine\StateMachineInterface;
use spec\SM\DummyObject;

class CascadeTransitionCallbackSpec extends ObjectBehavior
{
    function let(FactoryInterface $factory)
    {
        $this->beConstructedWith($factory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('SM\Callback\CascadeTransitionCallback');
    }

    function it_applies($factory, TransitionEvent $event, DummyObject $object, StateMachineInterface $sm)
    {
        $factory->get($object, 'graph')->willReturn($sm);

        $sm->can('transition')->willReturn(true);
        $sm->apply('transition', true)->shouldBeCalled();

        $this->apply($object, $event, 'transition', 'graph');
    }

    function it_applies_with_default_graph(
        $factory,
        TransitionEvent $event,
        DummyObject $object,
        StateMachineInterface $sm1,
        StateMachineInterface $sm2
    ) {
        $event->getStateMachine()->willReturn($sm2);

        $sm2->getGraph()->willReturn('graph');

        $factory->get($object, 'graph')->willReturn($sm1);

        $sm1->can('transition')->willReturn(true);
        $sm1->apply('transition', true)->shouldBeCalled();

        $this->apply($object, $event, 'transition');
    }

    function it_applies_with_default_graph_and_default_transition(
        $factory,
        TransitionEvent $event,
        DummyObject $object,
        StateMachineInterface $sm1,
        StateMachineInterface $sm2
    ) {
        $event->getStateMachine()->willReturn($sm2);
        $event->getTransition()->willReturn('transition');

        $sm2->getGraph()->willReturn('graph');

        $factory->get($object, 'graph')->willReturn($sm1);

        $sm1->can('transition')->willReturn(true);
        $sm1->apply('transition', true)->shouldBeCalled();

        $this->apply($object, $event);
    }
}
