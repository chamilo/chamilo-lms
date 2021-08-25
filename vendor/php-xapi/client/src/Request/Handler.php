<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Client\Request;

use Http\Client\Exception;
use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use Psr\Http\Message\RequestInterface;
use Xabbuh\XApi\Common\Exception\AccessDeniedException;
use Xabbuh\XApi\Common\Exception\ConflictException;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Common\Exception\XApiException;

/**
 * Prepares and executes xAPI HTTP requests.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class Handler implements HandlerInterface
{
    private $httpClient;
    private $requestFactory;
    private $baseUri;
    private $version;

    /**
     * @param HttpClient     $httpClient     The HTTP client sending requests to the remote LRS
     * @param RequestFactory $requestFactory The factory used to create PSR-7 HTTP requests
     * @param string         $baseUri        The APIs base URI (all end points will be created relatively to this URI)
     * @param string         $version        The xAPI version
     */
    public function __construct(HttpClient $httpClient, RequestFactory $requestFactory, $baseUri, $version)
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->baseUri = $baseUri;
        $this->version = $version;
    }

    /**
     * {@inheritDoc}
     */
    public function createRequest($method, $uri, array $urlParameters = array(), $body = null, array $headers = array())
    {
        if (!in_array(strtoupper($method), array('GET', 'POST', 'PUT', 'DELETE'))) {
            throw new \InvalidArgumentException(sprintf('"%s" is no valid HTTP method (expected one of [GET, POST, PUT, DELETE]) in an xAPI context.', $method));
        }

        $uri = rtrim($this->baseUri, '/').'/'.ltrim($uri, '/');

        if (count($urlParameters) > 0) {
            $uri .= '?'.http_build_query($urlParameters);
        }

        if (!isset($headers['X-Experience-API-Version'])) {
            $headers['X-Experience-API-Version'] = $this->version;
        }

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        return $this->requestFactory->createRequest(strtoupper($method), $uri, $headers, $body);
    }

    /**
     * {@inheritDoc}
     */
    public function executeRequest(RequestInterface $request, array $validStatusCodes)
    {
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (Exception $e) {
            throw new XApiException($e->getMessage(), $e->getCode(), $e);
        }

        // catch some common errors
        if (in_array($response->getStatusCode(), array(401, 403))) {
            throw new AccessDeniedException(
                (string) $response->getBody(),
                $response->getStatusCode()
            );
        } elseif (404 === $response->getStatusCode()) {
            throw new NotFoundException((string) $response->getBody());
        } elseif (409 === $response->getStatusCode()) {
            throw new ConflictException((string) $response->getBody());
        }

        if (!in_array($response->getStatusCode(), $validStatusCodes)) {
            throw new XApiException((string) $response->getBody(), $response->getStatusCode());
        }

        return $response;
    }
}
