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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Xabbuh\XApi\Common\Exception\XApiException;

/**
 * Prepare and execute xAPI HTTP requests.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
interface HandlerInterface
{
    /**
     * @param string $method        The HTTP method
     * @param string $uri           The URI to send the request to
     * @param array  $urlParameters Optional url parameters
     * @param string $body          An optional request body
     * @param array  $headers       Optional additional HTTP headers
     *
     * @return RequestInterface The request
     *
     * @throws \InvalidArgumentException when no valid HTTP method is given
     */
    public function createRequest($method, $uri, array $urlParameters = array(), $body = null, array $headers = array());

    /**
     * Performs the given HTTP request.
     *
     * @param RequestInterface $request          The HTTP request to perform
     * @param int[]            $validStatusCodes A list of HTTP status codes
     *                                           the calling method is able to
     *                                           handle
     *
     * @return ResponseInterface The remote server's response
     *
     * @throws XApiException when the request fails
     */
    public function executeRequest(RequestInterface $request, array $validStatusCodes);
}
