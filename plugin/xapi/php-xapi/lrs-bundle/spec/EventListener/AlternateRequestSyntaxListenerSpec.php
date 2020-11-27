<?php

namespace spec\XApi\LrsBundle\EventListener;

use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class AlternateRequestSyntaxListenerSpec extends ObjectBehavior
{
    function let(GetResponseEvent $event, Request $request, ParameterBag $query, ParameterBag $post, ParameterBag $attributes, HeaderBag $headers)
    {
        $query->count()->willReturn(1);
        $query->get('method')->willReturn('GET');

        $post->getIterator()->willReturn(new \ArrayIterator());
        $post->get('content')->willReturn(null);

        $attributes->has('xapi_lrs.route')->willReturn(true);

        $request->query = $query;
        $request->request = $post;
        $request->attributes = $attributes;
        $request->headers = $headers;
        $request->getMethod()->willReturn('POST');

        $event->isMasterRequest()->willReturn(true);
        $event->getRequest()->willReturn($request);
    }

    function it_returns_null_if_request_is_not_master(GetResponseEvent $event)
    {
        $event->isMasterRequest()->willReturn(false);
        $event->getRequest()->shouldNotBeCalled();

        $this->onKernelRequest($event)->shouldReturn(null);
    }

    function it_returns_null_if_request_has_no_attribute_xapi_lrs_route(GetResponseEvent $event, ParameterBag $attributes)
    {
        $attributes->has('xapi_lrs.route')->shouldBeCalled()->willReturn(false);

        $this->onKernelRequest($event)->shouldReturn(null);
    }

    function it_returns_null_if_request_method_is_get(GetResponseEvent $event, Request $request, ParameterBag $query)
    {
        $query->get('method')->shouldNotBeCalled();
        $request->getMethod()->willReturn('GET');

        $this->onKernelRequest($event)->shouldReturn(null);
    }

    function it_returns_null_if_request_method_is_put(GetResponseEvent $event, Request $request, ParameterBag $query)
    {
        $query->get('method')->shouldNotBeCalled();
        $request->getMethod()->willReturn('PUT');

        $this->onKernelRequest($event)->shouldReturn(null);
    }

    function it_throws_a_badrequesthttpexception_if_other_query_parameter_than_method_is_set(GetResponseEvent $event, ParameterBag $query)
    {
        $query->count()->shouldBeCalled()->willReturn(2);

        $this
            ->shouldThrow('\Symfony\Component\HttpKernel\Exception\BadRequestHttpException')
            ->during('onKernelRequest', array($event));
    }

    function it_sets_the_request_method_equals_to_method_query_parameter(GetResponseEvent $event, Request $request, ParameterBag $query)
    {
        $query->remove('method')->shouldBeCalled();
        $request->setMethod('GET')->shouldBeCalled();

        $this->onKernelRequest($event);
    }

    function it_sets_defined_post_parameters_as_header(GetResponseEvent $event, Request $request, ParameterBag $query, ParameterBag $post, HeaderBag $headers)
    {
        $request->setMethod('GET')->shouldBeCalled();
        $query->remove('method')->shouldBeCalled();

        $headerList = array(
            'Authorization' => 'Authorization',
            'X-Experience-API-Version' => 'X-Experience-API-Version',
            'Content-Type' => 'Content-Type',
            'Content-Length' => 'Content-Length',
            'If-Match' => 'If-Match',
            'If-None-Match' => 'If-None-Match',
        );

        $post->getIterator()->shouldBeCalled()->willReturn(new \ArrayIterator($headerList));

        foreach ($headerList as $key => $value) {
            $post->remove($key)->shouldBeCalled();

            $headers->set($key, $value)->shouldBeCalled();
        }

        $this->onKernelRequest($event);
    }

    function it_sets_other_post_parameters_as_query_parameters(GetResponseEvent $event, Request $request, ParameterBag $query, ParameterBag $post)
    {
        $request->setMethod('GET')->shouldBeCalled();
        $query->remove('method')->shouldBeCalled();

        $parameterList = array(
            'token' => 'a-token',
            'attachments' => true,
        );

        $post->getIterator()->shouldBeCalled()->willReturn(new \ArrayIterator($parameterList));

        foreach ($parameterList as $key => $value) {
            $post->remove($key)->shouldBeCalled();

            $query->set($key, $value)->shouldBeCalled();
        }

        $this->onKernelRequest($event);
    }

    function it_sets_content_from_post_parameters(GetResponseEvent $event, Request $request, ParameterBag $query, ParameterBag $post, ParameterBag $attributes, ParameterBag $cookies, FileBag $files, ServerBag $server)
    {
        $query->all()->shouldBeCalled()->willReturn(array());
        $query->remove('method')->shouldBeCalled();

        $post->all()->shouldBeCalled()->willReturn(array());
        $post->get('content')->shouldBeCalled()->willReturn('a content');
        $post->remove('content')->shouldBeCalled();

        $attributes->all()->shouldBeCalled()->willReturn(array());
        $cookies->all()->shouldBeCalled()->willReturn(array());
        $files->all()->shouldBeCalled()->willReturn(array());
        $server->all()->shouldBeCalled()->willReturn(array());

        $request->setMethod('GET')->shouldBeCalled();
        $request->initialize(
            array(),
            array(),
            array(),
            array(),
            array(),
            array(),
            'a content'
        )->shouldBeCalled();

        $request->cookies = $cookies;
        $request->files = $files;
        $request->server = $server;

        $this->onKernelRequest($event);
    }
}
