<?php

namespace spec\XApi\LrsBundle\Response;

use PhpSpec\ObjectBehavior;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MultipartResponseSpec extends ObjectBehavior
{
    function let(JsonResponse $statementResponse)
    {
        $this->beConstructedWith($statementResponse);
    }

    function it_should_throw_a_logicexception_when_setting_content()
    {
        $this
            ->shouldThrow('\LogicException')
            ->during('setContent', array('a custom content'));
    }

    function it_should_set_Content_Type_header_of_a_multipart_response()
    {
        $request = new Request();

        $this->prepare($request);

        $this->headers->get('Content-Type')->shouldStartWith('multipart/mixed; boundary=');
    }
}
