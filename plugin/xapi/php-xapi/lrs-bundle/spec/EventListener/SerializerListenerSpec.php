<?php

namespace spec\XApi\LrsBundle\EventListener;

use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Xabbuh\XApi\Serializer\StatementSerializerInterface;
use XApi\Fixtures\Json\StatementJsonFixtures;

class SerializerListenerSpec extends ObjectBehavior
{
    function let(StatementSerializerInterface $statementSerializer, GetResponseEvent $event, Request $request, ParameterBag $attributes)
    {
        $attributes->has('xapi_lrs.route')->willReturn(true);

        $request->attributes = $attributes;

        $event->getRequest()->willReturn($request);

        $this->beConstructedWith($statementSerializer);
    }

    function it_returns_null_if_request_has_no_attribute_xapi_lrs_route(GetResponseEvent $event, ParameterBag $attributes)
    {
        $attributes->has('xapi_lrs.route')->shouldBeCalled()->willReturn(false);
        $attributes->get('xapi_serializer')->shouldNotBeCalled();

        $this->onKernelRequest($event)->shouldReturn(null);
    }

    function it_sets_unserialized_data_as_request_attributes(StatementSerializerInterface $statementSerializer, GetResponseEvent $event, Request $request, ParameterBag $attributes)
    {
        $jsonString = StatementJsonFixtures::getTypicalStatement();

        $statementSerializer->deserializeStatement($jsonString)->shouldBeCalled();

        $attributes->get('xapi_serializer')->willReturn('statement');
        $attributes->set('statement', null)->shouldBeCalled();

        $request->getContent()->shouldBeCalled()->willReturn($jsonString);

        $this->onKernelRequest($event);
    }

    function it_throws_a_badrequesthttpexception_if_the_serializer_fails(StatementSerializerInterface $statementSerializer, GetResponseEvent $event, Request $request, ParameterBag $attributes)
    {
        $statementSerializer->deserializeStatement(null)->shouldBeCalled()->willThrow('\Symfony\Component\Serializer\Exception\InvalidArgumentException');

        $attributes->get('xapi_serializer')->willReturn('statement');

        $request->attributes = $attributes;

        $this
            ->shouldThrow('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException')
            ->during('onKernelRequest', array($event));
    }
}
