<?php

namespace spec\Xabbuh\XApi\Client;

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\RequestFactory;
use PhpSpec\Exception\Example\SkippingException;
use PhpSpec\ObjectBehavior;
use Xabbuh\Http\Authentication\OAuth1;
use Xabbuh\XApi\Client\XApiClientBuilderInterface;
use Xabbuh\XApi\Client\XApiClientInterface;

class XApiClientBuilderSpec extends ObjectBehavior
{
    function it_is_an_xapi_client_builder()
    {
        $this->shouldHaveType(XApiClientBuilderInterface::class);
    }

    function it_creates_an_xapi_client(HttpClient $httpClient, RequestFactory $requestFactory)
    {
        $this->setHttpClient($httpClient);
        $this->setRequestFactory($requestFactory);
        $this->setBaseUrl('http://example.com/xapi/');
        $this->build()->shouldHaveType(XApiClientInterface::class);
    }

    function its_methods_can_be_chained(HttpClient $httpClient, RequestFactory $requestFactory)
    {
        $this->setHttpClient($httpClient)->shouldReturn($this);
        $this->setRequestFactory($requestFactory)->shouldReturn($this);
        $this->setBaseUrl('http://example.com/xapi/')->shouldReturn($this);
        $this->setVersion('1.0.0')->shouldReturn($this);
        $this->setAuth('foo', 'bar')->shouldReturn($this);
        $this->setOAuthCredentials('consumer key', 'consumer secret', 'token', 'token secret')->shouldReturn($this);
    }

    function it_throws_an_exception_if_the_http_client_is_not_configured(RequestFactory $requestFactory)
    {
        if ($this->isAbleToDiscoverHttpClient()) {
            throw new SkippingException('The builder does not throw an exception if it can automatically discover an HTTP client.');
        }

        $this->setRequestFactory($requestFactory);
        $this->setBaseUrl('http://example.com/xapi/');

        $this->shouldThrow('\LogicException')->during('build');
    }

    function it_throws_an_exception_if_the_request_factory_is_not_configured(HttpClient $httpClient)
    {
        if ($this->isAbleToDiscoverRequestFactory()) {
            throw new SkippingException('The builder does not throw an exception if it can automatically discover a request factory.');
        }

        $this->setHttpClient($httpClient);
        $this->setBaseUrl('http://example.com/xapi/');

        $this->shouldThrow('\LogicException')->during('build');
    }

    function it_can_build_the_client_when_it_is_able_to_discover_the_http_client_and_the_request_factory_without_configuring_them_explicitly()
    {
        if (!class_exists(HttpClientDiscovery::class)) {
            throw new SkippingException(sprintf('The "%s" class is required to let the builder auto discover the HTTP client and request factory.', HttpClientDiscovery::class));
        }

        if (!$this->isAbleToDiscoverHttpClient()) {
            throw new SkippingException('Unable to discover an HTTP client.');
        }

        if (!$this->isAbleToDiscoverRequestFactory()) {
            throw new SkippingException('Unable to discover a request factory.');
        }

        $this->setBaseUrl('http://example.com/xapi/');

        $this->build()->shouldReturnAnInstanceOf(XApiClientInterface::class);
    }

    function it_throws_an_exception_if_the_base_uri_is_not_configured(HttpClient $httpClient, RequestFactory $requestFactory)
    {
        $this->setHttpClient($httpClient);
        $this->setRequestFactory($requestFactory);

        $this->shouldThrow('\LogicException')->during('build');
    }

    function it_throws_an_exception_when_oauth_credentials_are_configured_but_the_auth_package_is_missing(HttpClient $httpClient, RequestFactory $requestFactory)
    {
        if (class_exists(OAuth1::class)) {
            throw new SkippingException('OAuth1 credentials can be used when the "xabbuh/oauth1-authentication" package is present.');
        }

        $this->setHttpClient($httpClient);
        $this->setRequestFactory($requestFactory);
        $this->setBaseUrl('http://example.com/xapi/');
        $this->setOAuthCredentials('consumer_key', 'consumer_secret', 'access_token', 'token_secret');

        $this->shouldThrow(new \LogicException('The "xabbuh/oauth1-authentication package is needed to use OAuth1 authorization.'))->during('build');
    }

    function it_accepts_oauth_credentials_when_the_auth_package_is_present(HttpClient $httpClient, RequestFactory $requestFactory)
    {
        if (!class_exists(OAuth1::class)) {
            throw new SkippingException('OAuth1 credentials cannot be used when the "xabbuh/oauth1-authentication" package is missing.');
        }

        $this->setHttpClient($httpClient);
        $this->setRequestFactory($requestFactory);
        $this->setBaseUrl('http://example.com/xapi/');
        $this->setOAuthCredentials('consumer_key', 'consumer_secret', 'access_token', 'token_secret');
        $this->build();
    }

    private function isAbleToDiscoverHttpClient()
    {
        try {
            HttpClientDiscovery::find();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isAbleToDiscoverRequestFactory()
    {
        try {
            MessageFactoryDiscovery::find();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
