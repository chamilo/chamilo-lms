<?php

namespace spec\Xabbuh\XApi\Client\Request;

use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Xabbuh\XApi\Common\Exception\AccessDeniedException;
use Xabbuh\XApi\Common\Exception\ConflictException;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Common\Exception\XApiException;

class HandlerSpec extends ObjectBehavior
{
    function let(HttpClient $client, RequestFactory $requestFactory)
    {
        $this->beConstructedWith($client, $requestFactory, 'http://example.com/xapi/', '1.0.1');
    }

    function it_throws_an_exception_if_a_request_is_created_with_an_invalid_method()
    {
        $this->shouldThrow('\InvalidArgumentException')->during('createRequest', array('options', '/xapi/statements'));
    }

    function it_returns_get_request_created_by_the_http_client(RequestFactory $requestFactory, RequestInterface $request)
    {
        $requestFactory->createRequest('GET', 'http://example.com/xapi/statements', array(
            'X-Experience-API-Version' => '1.0.1',
            'Content-Type' => 'application/json',
        ), null)->willReturn($request);

        $this->createRequest('get', '/statements')->shouldReturn($request);
        $this->createRequest('GET', '/statements')->shouldReturn($request);
    }

    function it_returns_post_request_created_by_the_http_client(RequestFactory $requestFactory, RequestInterface $request)
    {
        $requestFactory->createRequest('POST', 'http://example.com/xapi/statements', array(
            'X-Experience-API-Version' => '1.0.1',
            'Content-Type' => 'application/json',
        ), 'body')->willReturn($request);

        $this->createRequest('post', '/statements', array(), 'body')->shouldReturn($request);
        $this->createRequest('POST', '/statements', array(), 'body')->shouldReturn($request);
    }

    function it_returns_put_request_created_by_the_http_client(RequestFactory $requestFactory, RequestInterface $request)
    {
        $requestFactory->createRequest('PUT', 'http://example.com/xapi/statements', array(
            'X-Experience-API-Version' => '1.0.1',
            'Content-Type' => 'application/json',
        ), 'body')->willReturn($request);

        $this->createRequest('put', '/statements', array(), 'body')->shouldReturn($request);
        $this->createRequest('PUT', '/statements', array(), 'body')->shouldReturn($request);
    }

    function it_returns_delete_request_created_by_the_http_client(RequestFactory $requestFactory, RequestInterface $request)
    {
        $requestFactory->createRequest('DELETE', 'http://example.com/xapi/statements', array(
            'X-Experience-API-Version' => '1.0.1',
            'Content-Type' => 'application/json',
        ), null)->willReturn($request);

        $this->createRequest('delete', '/statements')->shouldReturn($request);
        $this->createRequest('DELETE', '/statements')->shouldReturn($request);
    }

    function it_throws_an_access_denied_exception_when_a_401_status_code_is_returned(HttpClient $client, RequestInterface $request, ResponseInterface $response)
    {
        $client->sendRequest($request)->willReturn($response);
        $response->getStatusCode()->willReturn(401);
        $response->getBody()->willReturn('body');

        $this->shouldThrow(AccessDeniedException::class)->during('executeRequest', array($request, array(200)));
    }

    function it_throws_an_access_denied_exception_when_a_403_status_code_is_returned(HttpClient $client, RequestInterface $request, ResponseInterface $response)
    {
        $client->sendRequest($request)->willReturn($response);
        $response->getStatusCode()->willReturn(403);
        $response->getBody()->willReturn('body');

        $this->shouldThrow(AccessDeniedException::class)->during('executeRequest', array($request, array(200)));
    }

    function it_throws_a_not_found_exception_when_a_404_status_code_is_returned(HttpClient $client, RequestInterface $request, ResponseInterface $response)
    {
        $client->sendRequest($request)->willReturn($response);
        $response->getStatusCode()->willReturn(404);
        $response->getBody()->willReturn('body');

        $this->shouldThrow(NotFoundException::class)->during('executeRequest', array($request, array(200)));
    }

    function it_throws_a_conflict_exception_when_a_409_status_code_is_returned(HttpClient $client, RequestInterface $request, ResponseInterface $response)
    {
        $client->sendRequest($request)->willReturn($response);
        $response->getStatusCode()->willReturn(409);
        $response->getBody()->willReturn('body');

        $this->shouldThrow(ConflictException::class)->during('executeRequest', array($request, array(200)));
    }

    function it_throws_an_xapi_exception_when_an_unexpected_status_code_is_returned(HttpClient $client, RequestInterface $request, ResponseInterface $response)
    {
        $client->sendRequest($request)->willReturn($response);
        $response->getStatusCode()->willReturn(204);
        $response->getBody()->willReturn('body');

        $this->shouldThrow(XApiException::class)->during('executeRequest', array($request, array(200)));
    }

    function it_returns_the_response_on_success(HttpClient $client, RequestInterface $request, ResponseInterface $response)
    {
        $client->sendRequest($request)->willReturn($response);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn('body');

        $this->executeRequest($request, array(200))->shouldReturn($response);
    }
}
