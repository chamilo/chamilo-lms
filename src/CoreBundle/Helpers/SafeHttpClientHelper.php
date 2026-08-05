<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Factory for SSRF-safe outbound HTTP fetches of user-controlled URLs.
 *
 * Single home for the control shared by every server-side fetcher in the
 * codebase: the cartridge auto-detect (ImsLti), the link checker and the
 * learnpath X-Frame-Options probe. The returned client refuses targets that
 * resolve to loopback/private/reserved ranges (incl. the cloud metadata
 * endpoint 169.254.169.254), re-validates every redirect hop, speaks only
 * http(s) and verifies TLS.
 */
final class SafeHttpClientHelper
{
    /**
     * SSRF-safe HTTP client. Construction has no side effects, so it is safe to
     * call from any context (no platform settings or container required).
     */
    public static function create(): HttpClientInterface
    {
        return new NoPrivateNetworkHttpClient(HttpClient::create());
    }

    /**
     * Merge Chamilo's optional proxy platform setting into request options.
     *
     * curl exposes the proxy as host + port; HttpClient wants a single proxy URL
     * with a scheme (a bare host is parsed as a path and rejected), so normalise
     * it here. Reads the `security.proxy_settings` setting through the container,
     * hence callable only from a fully booted context.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public static function withChamiloProxy(array $options = []): array
    {
        $proxySettings = Container::getSettingsManager()->getSetting('security.proxy_settings', true);

        if (!\is_array($proxySettings)
            || !isset($proxySettings['curl_setopt_array'])
            || !\is_array($proxySettings['curl_setopt_array'])
        ) {
            return $options;
        }

        $curlOptions = $proxySettings['curl_setopt_array'];
        $host = $curlOptions['CURLOPT_PROXY'] ?? null;

        if (empty($host)) {
            return $options;
        }

        $port = $curlOptions['CURLOPT_PROXYPORT'] ?? null;
        $proxy = !empty($port) ? $host.':'.$port : (string) $host;

        if (!preg_match('~^[a-z][a-z0-9+.\-]*://~i', $proxy)) {
            $proxy = 'http://'.$proxy;
        }

        $options['proxy'] = $proxy;

        return $options;
    }
}
