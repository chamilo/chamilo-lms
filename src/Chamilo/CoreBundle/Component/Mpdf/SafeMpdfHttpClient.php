<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Mpdf;

use Chamilo\CoreBundle\Component\Http\SafeHttp;
use Mpdf\Container\SimpleContainer;
use Mpdf\Http\ClientInterface;
use Mpdf\PsrHttpMessageShim\Response;
use Mpdf\PsrHttpMessageShim\Stream;
use Psr\Http\Message\RequestInterface;

/**
 * SSRF-safe HTTP client for mPDF.
 *
 * mPDF fetches every remote `<img src>` and CSS `url()` server-side while
 * rendering. By default it uses its internal cURL/socket client with no IP
 * filtering, which lets attacker-controlled wiki/document HTML reach the cloud
 * metadata endpoint (169.254.169.254) or internal services (SSRF).
 *
 * This client is injected into mPDF through its experimental container (2nd
 * constructor argument) and refuses any URL that resolves to a loopback,
 * private, reserved or link-local address. The connection is pinned to the
 * already-validated IP (CURLOPT_RESOLVE) and redirects are disabled so a remote
 * 30x cannot bounce the request to an internal host.
 *
 * The 1.11.x branch ships Symfony 3.4, which has no NoPrivateNetworkHttpClient,
 * so the validation is implemented here directly (the master branch relies on
 * Symfony\Component\HttpClient\NoPrivateNetworkHttpClient instead).
 */
final class SafeMpdfHttpClient implements ClientInterface
{
    /**
     * Builds the mPDF container exposing this client as "httpClient".
     *
     * Usage: new \Mpdf\Mpdf($config, SafeMpdfHttpClient::container());
     */
    public static function container(): SimpleContainer
    {
        return new SimpleContainer(["httpClient" => new self()]);
    }

    /**
     * @return Response
     */
    public function sendRequest(RequestInterface $request)
    {
        $response = new Response();

        $uri = (string) $request->getUri();
        $ip = SafeHttp::resolveSafeIp($uri);

        if (null === $ip) {
            // Blocked or unresolvable target: return an empty 403 so mPDF
            // silently skips the asset instead of embedding internal content.
            return $response->withStatus(403);
        }

        $parts = parse_url($uri);
        $host = $parts["host"];
        $scheme = strtolower($parts["scheme"] ?? "http");
        $port = (int) ($parts["port"] ?? ("https" === $scheme ? 443 : 80));

        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        // Never follow redirects: a 30x could point to an internal host that
        // would bypass the validation performed above.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        // Pin the connection to the IP we already validated to prevent a
        // DNS-rebinding race between resolution and the actual request.
        curl_setopt($ch, CURLOPT_RESOLVE, ["$host:$port:$ip"]);

        $data = curl_exec($ch);

        if (false === $data || curl_errno($ch)) {
            self::closeCurl($ch);

            return $response;
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        self::closeCurl($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            return $response->withStatus($httpCode ?: 502);
        }

        return $response
            ->withStatus($httpCode)
            ->withBody(Stream::create($data))
        ;
    }

    private static function closeCurl($ch): void
    {
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }
    }
}
