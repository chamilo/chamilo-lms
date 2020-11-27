<?php

namespace spec\XApi\LrsBundle\EventListener;

use PhpSpec\ObjectBehavior;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VersionListenerSpec extends ObjectBehavior
{
    function let(GetResponseEvent $getResponseEvent, FilterResponseEvent $filterResponseEvent, Request $request, ParameterBag $attributes, HeaderBag $requestHeaders)
    {
        $attributes->has('xapi_lrs.route')->willReturn(true);

        $request->attributes = $attributes;
        $request->headers = $requestHeaders;

        $getResponseEvent->isMasterRequest()->willReturn(true);
        $getResponseEvent->getRequest()->willReturn($request);

        $filterResponseEvent->isMasterRequest()->willReturn(true);
        $filterResponseEvent->getRequest()->willReturn($request);
    }

    function it_returns_null_if_requests_are_not_master(GetResponseEvent $getResponseEvent, FilterResponseEvent $filterResponseEvent)
    {
        $getResponseEvent->isMasterRequest()->willReturn(false);
        $getResponseEvent->getRequest()->shouldNotBeCalled();

        $this->onKernelRequest($getResponseEvent)->shouldReturn(null);

        $filterResponseEvent->isMasterRequest()->willReturn(false);
        $filterResponseEvent->getRequest()->shouldNotBeCalled();

        $this->onKernelResponse($filterResponseEvent)->shouldReturn(null);
    }

    function it_returns_null_if_not_xapi_route(GetResponseEvent $getResponseEvent, FilterResponseEvent $filterResponseEvent, ParameterBag $attributes)
    {
        $attributes->has('xapi_lrs.route')->shouldBeCalled()->willReturn(false);

        $this->onKernelRequest($getResponseEvent)->shouldReturn(null);

        $this->onKernelResponse($filterResponseEvent)->shouldReturn(null);
    }

    function it_throws_a_badrequesthttpexception_if_no_X_Experience_API_Version_header_is_set(GetResponseEvent $getResponseEvent, HeaderBag $requestHeaders)
    {
        $requestHeaders->get('X-Experience-API-Version')->shouldBeCalled()->willReturn(null);

        $this
            ->shouldThrow(new BadRequestHttpException('Missing required "X-Experience-API-Version" header.'))
            ->during('onKernelRequest', array($getResponseEvent));
    }

    function it_throws_a_badrequesthttpexception_if_specified_version_is_not_supported(GetResponseEvent $getResponseEvent, HeaderBag $requestHeaders)
    {
        $requestHeaders->get('X-Experience-API-Version')->shouldBeCalled()->willReturn('0.9.5');

        $this
            ->shouldThrow(new BadRequestHttpException('xAPI version "0.9.5" is not supported.'))
            ->during('onKernelRequest', array($getResponseEvent));

        $requestHeaders->get('X-Experience-API-Version')->shouldBeCalled()->willReturn('1.1.0');

        $this
            ->shouldThrow(new BadRequestHttpException('xAPI version "1.1.0" is not supported.'))
            ->during('onKernelRequest', array($getResponseEvent));
    }

    function it_normalizes_the_X_Experience_API_Version_header(GetResponseEvent $getResponseEvent, HeaderBag $requestHeaders)
    {
        $requestHeaders->get('X-Experience-API-Version')->shouldBeCalled()->willReturn('1.0');
        $requestHeaders->set('X-Experience-API-Version', '1.0.0')->shouldBeCalled();

        $this->onKernelRequest($getResponseEvent);
    }

    function it_returns_null_if_version_is_supported(GetResponseEvent $getResponseEvent, HeaderBag $requestHeaders)
    {
        $requestHeaders->get('X-Experience-API-Version')->shouldBeCalled()->willReturn('1.0.0');

        $this->onKernelRequest($getResponseEvent)->shouldReturn(null);
    }

    function it_sets_a_X_Experience_API_Version_header_in_response(FilterResponseEvent $filterResponseEvent, Response $response, HeaderBag $responseHeaders)
    {
        $responseHeaders->has('X-Experience-API-Version')->shouldBeCalled()->willReturn(false);
        $responseHeaders->set('X-Experience-API-Version', '1.0.3')->shouldBeCalled();

        $response->headers = $responseHeaders;

        $filterResponseEvent->getResponse()->shouldBeCalled()->willReturn($response);

        $this->onKernelResponse($filterResponseEvent);
    }
}
