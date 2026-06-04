<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\Mpdf;

use Chamilo\CoreBundle\Helpers\SafeHttpClientHelper;
use Mpdf\Container\SimpleContainer;
use Mpdf\Http\ClientInterface;
use Mpdf\PsrHttpMessageShim\Response;
use Mpdf\PsrHttpMessageShim\Stream;
use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * SSRF-safe HTTP client for mPDF (advisory 22.5).
 *
 * When rendering user-authored HTML to PDF, mPDF fetches remote `<img src>` /
 * `@import url()` server-side through its AssetFetcher; the bundled cURL/socket
 * clients have no private-IP filtering, so a student embedding
 * `<img src="http://169.254.169.254/...">` in a portfolio item and exporting to
 * PDF triggers a server-side request to an internal host. Passing
 * `new Mpdf($config, SafeMpdfHttpClient::container())` routes every remote asset
 * fetch through SafeHttpClientHelper, which refuses loopback/private/reserved
 * targets, validates each redirect hop and speaks only http(s). Public images
 * keep loading unchanged.
 */
final class SafeMpdfHttpClient implements ClientInterface
{
    /**
     * Ready-to-use mPDF container that overrides only the HTTP client.
     */
    public static function container(): SimpleContainer
    {
        return new SimpleContainer(['httpClient' => new self()]);
    }

    public function sendRequest(RequestInterface $request): Response
    {
        $response = new Response();

        try {
            $remote = SafeHttpClientHelper::create()->request('GET', (string) $request->getUri());
            $statusCode = $remote->getStatusCode();
            // false: read the body regardless of HTTP status (AssetFetcher checks 2xx itself).
            $content = $remote->getContent(false);
        } catch (ExceptionInterface $e) {
            // Blocked, unreachable or transport error: empty response so mPDF skips the asset.
            return $response;
        }

        return $response
            ->withStatus($statusCode)
            ->withBody(Stream::create($content))
        ;
    }
}
