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

use Http\Client\HttpClient;
use Http\Message\RequestFactory;

/**
 * xAPI client builder.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
interface XApiClientBuilderInterface
{
    /**
     * Sets the HTTP client implementation that will be used to issue HTTP requests.
     *
     * @param HttpClient $httpClient The HTTP client implementation
     *
     * @return XApiClientBuilderInterface The builder
     */
    public function setHttpClient(HttpClient $httpClient);

    /**
     * Sets the requests factory which creates requests that are then handled by the HTTP client.
     *
     * @param RequestFactory $requestFactory The request factory
     *
     * @return XApiClientBuilderInterface The builder
     */
    public function setRequestFactory(RequestFactory $requestFactory);

    /**
     * Sets the LRS base URL.
     *
     * @param string $baseUrl The base url
     *
     * @return XApiClientBuilderInterface The builder
     */
    public function setBaseUrl($baseUrl);

    /**
     * Sets the xAPI version.
     *
     * @param string $version The version to use
     *
     * @return XApiClientBuilderInterface The builder
     */
    public function setVersion($version);

    /**
     * Sets HTTP authentication credentials.
     *
     * @param string $username The username
     * @param string $password The password
     *
     * @return XApiClientBuilderInterface The builder
     */
    public function setAuth($username, $password);

    /**
     * Sets OAuth credentials.
     *
     * @param string $consumerKey    The consumer key
     * @param string $consumerSecret The consumer secret
     * @param string $token          The token
     * @param string $tokenSecret    The secret token
     *
     * @return XApiClientBuilderInterface The builder
     */
    public function setOAuthCredentials($consumerKey, $consumerSecret, $token, $tokenSecret);

    /**
     * Builds the xAPI client.
     *
     * @return XApiClientInterface The xAPI client
     *
     * @throws \LogicException if no base URI was configured
     */
    public function build();
}
