<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Client;

use ApiClients\Tools\Psr7\Oauth1\Definition\AccessToken;
use ApiClients\Tools\Psr7\Oauth1\Definition\ConsumerKey;
use ApiClients\Tools\Psr7\Oauth1\Definition\ConsumerSecret;
use ApiClients\Tools\Psr7\Oauth1\Definition\TokenSecret;
use ApiClients\Tools\Psr7\Oauth1\RequestSigning\RequestSigner;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\Authentication\BasicAuth;
use Http\Message\RequestFactory;
use Xabbuh\Http\Authentication\OAuth1;
use Xabbuh\XApi\Client\Request\Handler;
use Xabbuh\XApi\Serializer\SerializerFactoryInterface;
use Xabbuh\XApi\Serializer\SerializerRegistry;
use Xabbuh\XApi\Serializer\Symfony\SerializerFactory;

/**
 * xAPI client builder.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class XApiClientBuilder implements XApiClientBuilderInterface
{
    private $serializerFactory;

    /**
     * @var HttpClient|null
     */
    private $httpClient;

    /**
     * @var RequestFactory|null
     */
    private $requestFactory;

    private $baseUrl;
    private $version;
    private $username;
    private $password;
    private $consumerKey;
    private $consumerSecret;
    private $accessToken;
    private $tokenSecret;

    public function __construct(SerializerFactoryInterface $serializerFactory = null)
    {
        $this->serializerFactory = $serializerFactory ?: new SerializerFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestFactory(RequestFactory $requestFactory)
    {
        $this->requestFactory = $requestFactory;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setAuth($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setOAuthCredentials($consumerKey, $consumerSecret, $token, $tokenSecret)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->accessToken = $token;
        $this->tokenSecret = $tokenSecret;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function build()
    {
        if (null === $this->httpClient && class_exists(HttpClientDiscovery::class)) {
            try {
                $this->httpClient = HttpClientDiscovery::find();
            } catch (\Exception $e) {
            }
        }

        if (null === $httpClient = $this->httpClient) {
            throw new \LogicException('No HTTP client was configured.');
        }

        if (null === $this->requestFactory && class_exists(MessageFactoryDiscovery::class)) {
            try {
                $this->requestFactory = MessageFactoryDiscovery::find();
            } catch (\Exception $e) {
            }
        }

        if (null === $this->requestFactory) {
            throw new \LogicException('No request factory was configured.');
        }

        if (null === $this->baseUrl) {
            throw new \LogicException('Base URI value was not configured.');
        }

        $serializerRegistry = new SerializerRegistry();
        $serializerRegistry->setStatementSerializer($this->serializerFactory->createStatementSerializer());
        $serializerRegistry->setStatementResultSerializer($this->serializerFactory->createStatementResultSerializer());
        $serializerRegistry->setActorSerializer($this->serializerFactory->createActorSerializer());
        $serializerRegistry->setDocumentDataSerializer($this->serializerFactory->createDocumentDataSerializer());

        $plugins = array();

        if (null !== $this->username && null !== $this->password) {
            $plugins[] = new AuthenticationPlugin(new BasicAuth($this->username, $this->password));
        }

        if (null !== $this->consumerKey && null !== $this->consumerSecret && null !== $this->accessToken && null !== $this->tokenSecret) {
            if (!class_exists(OAuth1::class)) {
                throw new \LogicException('The "xabbuh/oauth1-authentication package is needed to use OAuth1 authorization.');
            }

            $requestSigner = new RequestSigner(new ConsumerKey($this->consumerKey), new ConsumerSecret($this->consumerSecret));
            $oauth = new OAuth1($requestSigner, new AccessToken($this->accessToken), new TokenSecret($this->tokenSecret));
            $plugins[] = new AuthenticationPlugin($oauth);
        }

        if (!empty($plugins)) {
            $httpClient = new PluginClient($httpClient, $plugins);
        }

        $version = null === $this->version ? '1.0.3' : $this->version;
        $requestHandler = new Handler($httpClient, $this->requestFactory, $this->baseUrl, $version);

        return new XApiClient($requestHandler, $serializerRegistry, $this->version);
    }
}
