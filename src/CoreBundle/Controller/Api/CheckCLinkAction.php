<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLink;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use const CURLINFO_HTTP_CODE;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_NOPROXY;
use const CURLOPT_PROTOCOLS;
use const CURLOPT_PROXY;
use const CURLOPT_REDIR_PROTOCOLS;
use const CURLOPT_RESOLVE;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const CURLPROTO_HTTP;
use const CURLPROTO_HTTPS;
use const DNS_AAAA;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_VALIDATE_IP;

final class CheckCLinkAction extends AbstractController
{
    public function __invoke(CLink $link, Request $request, SettingsManager $settingsManager): JsonResponse
    {
        $storedUrl = $link->getUrl();

        // Security filter: do not allow checking arbitrary URLs through this endpoint.
        $urlParam = $request->query->get('url');
        if (\is_string($urlParam) && '' !== trim($urlParam) && trim($urlParam) !== $storedUrl) {
            return new JsonResponse(['isValid' => false]);
        }

        $result = $this->checkUrl($storedUrl, $settingsManager);

        return new JsonResponse(['isValid' => $result]);
    }

    private function checkUrl(string $url, SettingsManager $settingsManager): bool
    {
        if (!\extension_loaded('curl')) {
            return false;
        }

        $target = $this->normalizeAndValidateUrl($url);
        if (null === $target) {
            return false;
        }

        // A proxy can resolve the hostname itself, bypassing CURLOPT_RESOLVE.
        // Fail closed rather than sending user-controlled URLs through a proxy.
        $proxySettings = $settingsManager->getSetting('security.proxy_settings', true);
        if (\is_array($proxySettings)
            && isset($proxySettings['curl_setopt_array'])
            && \is_array($proxySettings['curl_setopt_array'])
            && !empty($proxySettings['curl_setopt_array']['CURLOPT_PROXY'])
        ) {
            return false;
        }

        if (!\defined('CURLOPT_RESOLVE')) {
            return false;
        }

        $ch = curl_init();
        if (false === $ch) {
            return false;
        }

        if (!curl_setopt_array($ch, $this->createCurlOptions($target))) {
            curl_close($ch);

            return false;
        }

        $result = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (false === $result) {
            return false;
        }

        // Consider 2xx-3xx as valid.
        return $httpCode >= 200 && $httpCode < 400;
    }

    /**
     * Keep the original URL for Host, SNI and certificate verification while
     * forcing the connection to the IP already checked by the SSRF filter.
     *
     * @param array{url: string, host: string, port: int, ip: string, literal: bool} $target
     *
     * @return array<int, mixed>
     */
    private function createCurlOptions(array $target): array
    {
        $options = [
            CURLOPT_URL => $target['url'],
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_PROXY => '', // Also disable proxy environment variables.
            CURLOPT_NOPROXY => '*',
        ];

        if (\defined('CURLOPT_REDIR_PROTOCOLS')) {
            $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        if (!$target['literal']) {
            $ip = $target['ip'];
            if (str_contains($ip, ':')) {
                $ip = '['.$ip.']';
            }

            $options[CURLOPT_RESOLVE] = [$target['host'].':'.$target['port'].':'.$ip];
        }

        return $options;
    }

    /**
     * @return array{url: string, host: string, port: int, ip: string, literal: bool}|null
     */
    private function normalizeAndValidateUrl(string $url): ?array
    {
        $url = trim($url);
        if ('' === $url || str_contains($url, '\\') || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return null;
        }

        $parts = @parse_url($url);
        if (!\is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!\in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = (string) ($parts['host'] ?? '');
        if ('' === $host) {
            return null;
        }

        // parse_url() retains brackets around IPv6 literals.
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
            if (false === filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return null;
            }
        }

        $port = $parts['port'] ?? ('https' === $scheme ? 443 : 80);
        if (!\is_int($port) || $port < 1 || $port > 65535) {
            return null;
        }

        // Block credentials in URL: http://user:pass@host/
        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $lowerHost = strtolower($host);
        if ('localhost' === $lowerHost || str_ends_with($lowerHost, '.localhost')) {
            return null;
        }

        // Reject non-canonical hostnames that PHP and cURL may parse differently.
        $isIpLiteral = false !== filter_var($host, FILTER_VALIDATE_IP);
        if (!$isIpLiteral && !preg_match('/\A[a-z0-9.-]+\z/i', $host)) {
            return null;
        }

        // Resolve and block private/reserved ranges to mitigate SSRF.
        $ips = $this->resolveHostIps($host);
        if ([] === $ips) {
            return null;
        }

        foreach ($ips as $ip) {
            if (!$this->isAllowedPublicIp($ip)) {
                return null;
            }
        }

        return [
            'url' => $url,
            'host' => $host,
            'port' => $port,
            'ip' => $ips[0],
            'literal' => $isIpLiteral,
        ];
    }

    /**
     * @return string[]
     */
    private function resolveHostIps(string $host): array
    {
        $host = trim($host);
        if ('' === $host) {
            return [];
        }

        // If it's already an IP literal, validate it directly.
        if (false !== filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];

        // IPv4 A records
        $v4 = @gethostbynamel($host);
        if (\is_array($v4)) {
            foreach ($v4 as $ip) {
                if (\is_string($ip) && false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $ips[] = $ip;
                }
            }
        }

        // IPv6 AAAA records
        if (\function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_AAAA);
            if (\is_array($records)) {
                foreach ($records as $record) {
                    $ip = $record['ipv6'] ?? null;
                    if (\is_string($ip) && false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private function isAllowedPublicIp(string $ip): bool
    {
        // PHP 8.2 does not treat IPv4-mapped IPv6 (::ffff:127.0.0.1) as reserved: check the IPv4 inside.
        $packed = @inet_pton($ip);
        if (\is_string($packed) && 16 === \strlen($packed) && str_repeat("\0", 10)."\xff\xff" === substr($packed, 0, 12)) {
            $ipv4 = inet_ntop(substr($packed, 12));

            return \is_string($ipv4) && $this->isAllowedPublicIp($ipv4);
        }

        // First pass: reject private and reserved ranges using built-in filters.
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (false === filter_var($ip, FILTER_VALIDATE_IP, $flags)) {
            return false;
        }

        // Extra hardening: block CGNAT 100.64.0.0/10 explicitly (not always covered reliably).
        if (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $this->ipv4InCidr($ip, '100.64.0.0/10')) {
            return false;
        }

        return true;
    }

    private function ipv4InCidr(string $ip, string $cidr): bool
    {
        [$subnet, $maskBits] = explode('/', $cidr, 2);
        $maskBits = (int) $maskBits;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if (false === $ipLong || false === $subnetLong) {
            return false;
        }

        $mask = -1 << (32 - $maskBits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
