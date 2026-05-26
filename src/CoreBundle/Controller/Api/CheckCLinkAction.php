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
use const CURLOPT_PROTOCOLS;
use const CURLOPT_PROXY;
use const CURLOPT_PROXYPORT;
use const CURLOPT_REDIR_PROTOCOLS;
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

        $normalizedUrl = $this->normalizeAndValidateUrl($url);
        if (null === $normalizedUrl) {
            return false;
        }

        $defaults = [
            CURLOPT_URL => $normalizedUrl,
            CURLOPT_FOLLOWLOCATION => false, // Avoid SSRF via redirects
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ];

        if (\defined('CURLOPT_REDIR_PROTOCOLS')) {
            $defaults[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        // Optional proxy settings
        $proxySettings = $settingsManager->getSetting('security.proxy_settings', true);
        if (\is_array($proxySettings) && isset($proxySettings['curl_setopt_array']) && \is_array($proxySettings['curl_setopt_array'])) {
            $opts = $proxySettings['curl_setopt_array'];

            if (isset($opts['CURLOPT_PROXY'])) {
                $defaults[CURLOPT_PROXY] = $opts['CURLOPT_PROXY'];
            }
            if (isset($opts['CURLOPT_PROXYPORT'])) {
                $defaults[CURLOPT_PROXYPORT] = $opts['CURLOPT_PROXYPORT'];
            }
        }

        $ch = curl_init();
        curl_setopt_array($ch, $defaults);

        $result = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (false === $result) {
            return false;
        }

        // Consider 2xx-3xx as valid.
        return $httpCode >= 200 && $httpCode < 400;
    }

    private function normalizeAndValidateUrl(string $url): ?string
    {
        $url = trim($url);
        if ('' === $url) {
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

        // Block credentials in URL: http://user:pass@host/
        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $lowerHost = strtolower($host);
        if ('localhost' === $lowerHost || str_ends_with($lowerHost, '.localhost')) {
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

        return $url;
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
