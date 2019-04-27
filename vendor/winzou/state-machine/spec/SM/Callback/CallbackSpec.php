<?php

namespace spec\SM\Callback;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SM\Event\TransitionEvent;
use SM\StateMachine\StateMachineInterface;

class CallbackSpec extends ObjectBehavior
{
    protected $specs = array();
    protected $callable;
    protected $sm;

    function let(StateMachineInterface $sm)
    {
        $sm->getState()->willReturn('checkout');

        $this->beConstructedWith($this->specs, $this->callable);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('SM\Callback\Callback');
    }

    function it_satisfies_simple_on(TransitionEvent $event)
    {
        $specs = array('on' => 'tested-transition');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('dummy'), 'dummy'));
        $event->getTransition()->willReturn('tested-transition');
        $event->getState()->willReturn('dummy');

        $this->isSatisfiedBy($event)->shouldReturn(true);
    }

    function it_doesnt_satisfies_simple_on(TransitionEvent $event)
    {
        $specs = array('on' => 'tested-transition');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('dummy'), 'dummy'));
        $event->getTransition()->willReturn('tested-transition-not-matching');

        $this->isSatisfiedBy($event)->shouldReturn(false);
    }

    function it_satisfies_simple_from(TransitionEvent $event)
    {
        $specs = array('from' => 'tested-state');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('tested-state'), 'dummy'));
        $event->getTransition()->willReturn('dummy');
        $event->getState()->willReturn('tested-state');

        $this->isSatisfiedBy($event)->shouldReturn(true);
    }

    function it_doesnt_satisfies_simple_from(TransitionEvent $event)
    {
        $specs = array('from' => 'tested-state');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('tested-state-not-matching'), 'dummy'));
        $event->getTransition()->willReturn('dummy');
        $event->getState()->willReturn('tested-state-not-matching');

        $this->isSatisfiedBy($event)->shouldReturn(false);
    }

    function it_satisfies_simple_to(TransitionEvent $event)
    {
        $specs = array('to' => 'tested-state');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('dummy'), 'tested-state'));
        $event->getTransition()->willReturn('dummy');
        $event->getState()->willReturn('dummy');

        $this->isSatisfiedBy($event)->shouldReturn(true);
    }

    function it_doesnt_satisfies_simple_to(TransitionEvent $event)
    {
        $specs = array('from' => 'tested-state');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('tested-state-not-matching'), 'dummy'));
        $event->getTransition()->willReturn('dummy');
        $event->getState()->willReturn('dummy');

        $this->isSatisfiedBy($event)->shouldReturn(false);
    }

    function it_satisfies_complex_specs(TransitionEvent $event)
    {
        $specs = array('to' => 'tested-state-to', 'from' => 'tested-state-from', 'on' => 'tested-transition');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('tested-state-from'), 'tested-state-to'));
        $event->getTransition()->willReturn('tested-transition');
        $event->getState()->willReturn('tested-state-from');

        $this->isSatisfiedBy($event)->shouldReturn(true);
    }

    function it_doesnt_satisfies_wrong_from(TransitionEvent $event)
    {
        $specs = array('to' => 'tested-state-to', 'from' => 'tested-wrong', 'on' => 'tested-transition');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('dummy'), 'tested-state-to'));
        $event->getTransition()->willReturn('tested-transition');
        $event->getState()->willReturn('dummy');

        $this->isSatisfiedBy($event)->shouldReturn(false);
    }

    function it_doesnt_satisfies_wrong_to(TransitionEvent $event)
    {
        $specs = array('to' => 'tested-wrong', 'from' => 'tested-state-from', 'on' => 'tested-transition');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('tested-state-from'), 'dummy'));
        $event->getTransition()->willReturn('tested-transition');
        $event->getState()->willReturn('tested-state-from');

        $this->isSatisfiedBy($event)->shouldReturn(false);
    }

    function it_doesnt_satisfies_wrong_on(TransitionEvent $event)
    {
        $specs = array('to' => 'tested-state-to', 'from' => 'tested-state-from', 'on' => 'tested-wrong');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('tested-state-from'), 'tested-state-to'));
        $event->getTransition()->willReturn('dummy');
        $event->getState()->willReturn('tested-state-from');

        $this->isSatisfiedBy($event)->shouldReturn(false);
    }

    function it_doesnt_satisfies_excluded_from(TransitionEvent $event)
    {
        $specs = array('to' => 'tested-state-to', 'excluded_from' => 'tested-state-from');
        $this->beConstructedWith($specs, $this->callable);

        $event->getConfig()->willReturn($this->getConfig(array('tested-state-from'), 'tested-state-to'));
        $event->getTransition()->willReturn('dummy');
        $event->getState()->willReturn('tested-state-from');

        $this->isSatisfiedBy($event)->shouldReturn(false);
    }

    protected function getConfig($from = array(), $to)
    {
        return array('from' => $from, 'to' => $to);
    }
}
