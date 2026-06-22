<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Essence;

use Chamilo\CoreBundle\Component\Http\SafeHttp;
use Essence\Http\Client;
use Essence\Http\Exception as EssenceHttpException;

/**
 * SSRF-safe HTTP client for the Essence library.
 *
 * Essence fetches OEmbed/OpenGraph URLs server-side (Essence::_extract ->
 * Client::get) while rendering the course/session "about" pages from a
 * teacher-set video URL. Its bundled cURL/native clients have no private-IP
 * filtering and follow redirects, so the fetch could reach loopback/internal
 * hosts or the cloud metadata endpoint (CWE-918, SSRF).
 *
 * Injecting this client via
 * `Essence\Essence::instance(["Http" => new SafeEssenceHttpClient()])` routes
 * every Essence fetch through SafeHttp: it refuses loopback/private/reserved/
 * link-local targets, speaks only http(s), forbids redirects and pins the
 * validated IP (CURLOPT_RESOLVE) to defeat DNS rebinding.
 */
class SafeEssenceHttpClient extends Client
{
    /**
     * {@inheritDoc}
     */
    public function get($url)
    {
        $safeIp = SafeHttp::resolveSafeIp($url);

        if (null === $safeIp) {
            // Blocked or unresolvable target: surface Essence's own exception so
            // the provider skips the embed instead of reaching internal content.
            throw new EssenceHttpException($url, 403);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_userAgent);
        curl_setopt_array($ch, SafeHttp::secureCurlOptions($url, $safeIp));

        $contents = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $failed = false === $contents || curl_errno($ch);

        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        if ($failed) {
            throw new EssenceHttpException($url, $httpCode);
        }

        return $contents;
    }
}
