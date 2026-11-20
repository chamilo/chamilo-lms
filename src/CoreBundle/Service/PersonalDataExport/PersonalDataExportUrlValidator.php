<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\PersonalDataExport;

use InvalidArgumentException;

use const FILTER_VALIDATE_INT;
use const FILTER_VALIDATE_IP;
use const IDNA_DEFAULT;
use const INTL_IDNA_VARIANT_UTS46;

/**
 * Validates a user-supplied destination URL before Chamilo posts personal data to it.
 *
 * HTTPS is required. The host is resolved and every address is checked against
 * private, loopback, link-local, multicast and reserved ranges to prevent SSRF.
 */
final class PersonalDataExportUrlValidator
{
    public const int MAX_URL_LENGTH = 2048;

    /**
     * @var list<string>
     */
    private const array BLOCKED_IPV4_CIDRS = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '255.255.255.255/32',
    ];

    /**
     * @var list<string>
     */
    private const array BLOCKED_IPV6_CIDRS = [
        '::/128',
        '::1/128',
        '::ffff:0:0/96',
        '64:ff9b::/96',
        '64:ff9b:1::/48',
        '100::/64',
        '2001::/23',
        '2001:db8::/32',
        '2002::/16',
        'fc00::/7',
        'fe80::/10',
        'ff00::/8',
    ];

    /**
     * @var list<string>
     */
    private const array BLOCKED_HOSTNAMES = [
        'localhost',
        'localhost.localdomain',
        'ip6-localhost',
        'ip6-loopback',
        'metadata.google.internal',
    ];

    public function __construct(
        private readonly DnsHostResolver $dnsHostResolver
    ) {}

    /**
     * @return array{url: string, host: string, port: int, ips: list<string>, pin_ip: string}
     */
    public function validate(string $url): array
    {
        $url = trim($url);
        if ('' === $url) {
            throw new InvalidArgumentException('A destination URL is required.');
        }
        if (\strlen($url) > self::MAX_URL_LENGTH) {
            throw new InvalidArgumentException('The destination URL is too long.');
        }
        if (!str_starts_with(strtolower($url), 'https://')) {
            throw new InvalidArgumentException('The destination URL must use HTTPS.');
        }

        $parts = parse_url($url);
        if (!\is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('The destination URL is not valid.');
        }
        if ('https' !== strtolower((string) $parts['scheme'])) {
            throw new InvalidArgumentException('The destination URL must use HTTPS.');
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('The destination URL must not contain credentials.');
        }

        $host = $this->normalizeHost((string) $parts['host']);
        $port = filter_var(
            $parts['port'] ?? 443,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                    'max_range' => 65535,
                ],
            ]
        );
        if (false === $port) {
            throw new InvalidArgumentException('The destination URL has an invalid port.');
        }

        $this->assertHostNameAllowed($host);

        $ips = $this->resolvePublicIps($host);
        $canonical = $this->rebuildHttpsUrl($host, $port, $parts);

        return [
            'url' => $canonical,
            'host' => $host,
            'port' => $port,
            'ips' => $ips,
            'pin_ip' => $ips[0],
        ];
    }

    /**
     * Re-check an IP that a redirect or a later lookup might have produced.
     */
    public function assertPublicIp(string $ip): void
    {
        if ($this->isBlockedIp($ip)) {
            throw new InvalidArgumentException('The destination URL resolves to a disallowed address.');
        }
    }

    private function normalizeHost(string $host): string
    {
        $host = trim($host);
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }
        $host = rtrim($host, '.');
        $host = strtolower($host);

        if (\function_exists('idn_to_ascii') && !filter_var($host, FILTER_VALIDATE_IP)) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if (false === $ascii || '' === $ascii) {
                throw new InvalidArgumentException('The destination URL host is not valid.');
            }
            $host = strtolower($ascii);
        }

        return $host;
    }

    private function assertHostNameAllowed(string $host): void
    {
        if ('' === $host) {
            throw new InvalidArgumentException('The destination URL host is not valid.');
        }
        if (\in_array($host, self::BLOCKED_HOSTNAMES, true)) {
            throw new InvalidArgumentException('The destination URL host is not allowed.');
        }
        if (str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            throw new InvalidArgumentException('The destination URL host is not allowed.');
        }
        if ($this->looksLikeIp($host) && $this->isBlockedIp($host)) {
            throw new InvalidArgumentException('The destination URL resolves to a disallowed address.');
        }
    }

    /**
     * @return list<string>
     */
    private function resolvePublicIps(string $host): array
    {
        if ($this->looksLikeIp($host)) {
            $this->assertPublicIp($host);

            return [$this->canonicalizeIp($host)];
        }

        $resolved = $this->dnsHostResolver->resolve($host);
        if ([] === $resolved) {
            throw new InvalidArgumentException('The destination URL host could not be resolved.');
        }

        $ips = [];
        foreach ($resolved as $ip) {
            $canonical = $this->canonicalizeIp($ip);
            $this->assertPublicIp($canonical);
            $ips[] = $canonical;
        }

        return array_values(array_unique($ips));
    }

    private function looksLikeIp(string $host): bool
    {
        return false !== filter_var($host, FILTER_VALIDATE_IP);
    }

    private function canonicalizeIp(string $ip): string
    {
        $packed = inet_pton($ip);
        if (false === $packed) {
            throw new InvalidArgumentException('The destination URL resolves to a disallowed address.');
        }

        $canonical = inet_ntop($packed);
        if (false === $canonical) {
            throw new InvalidArgumentException('The destination URL resolves to a disallowed address.');
        }

        return $canonical;
    }

    private function isBlockedIp(string $ip): bool
    {
        $packed = inet_pton($ip);
        if (false === $packed) {
            return true;
        }

        if (4 === \strlen($packed)) {
            $canonical = inet_ntop($packed);
            if (false === $canonical) {
                return true;
            }

            foreach (self::BLOCKED_IPV4_CIDRS as $cidr) {
                if ($this->ipInCidr($canonical, $cidr)) {
                    return true;
                }
            }

            return false;
        }

        if (16 === \strlen($packed)) {
            $mappedIpv4 = $this->extractIpv4Mapped($packed);
            if (null !== $mappedIpv4) {
                return $this->isBlockedIp($mappedIpv4);
            }

            $canonical = inet_ntop($packed);
            if (false === $canonical) {
                return true;
            }

            foreach (self::BLOCKED_IPV6_CIDRS as $cidr) {
                if ($this->ipInCidr($canonical, $cidr)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    private function extractIpv4Mapped(string $packed): ?string
    {
        if (16 !== \strlen($packed)) {
            return null;
        }
        if ("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" !== substr($packed, 0, 12)) {
            return null;
        }
        $ipv4 = inet_ntop(substr($packed, 12, 4));

        return false === $ipv4 ? null : $ipv4;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bitsString] = explode('/', $cidr, 2);
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);
        if (false === $ipBin || false === $subnetBin || \strlen($ipBin) !== \strlen($subnetBin)) {
            return false;
        }

        $bits = (int) $bitsString;
        $length = \strlen($ipBin);
        $maxBits = 8 * $length;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        if (substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }

        $remaining = $bits % 8;
        if (0 === $remaining) {
            return true;
        }

        $mask = (0xFF << (8 - $remaining)) & 0xFF;

        return (\ord($ipBin[$fullBytes]) & $mask) === (\ord($subnetBin[$fullBytes]) & $mask);
    }

    /**
     * @param array<string, mixed> $parts
     */
    private function rebuildHttpsUrl(string $host, int $port, array $parts): string
    {
        $authority = $this->looksLikeIp($host) && str_contains($host, ':')
            ? '['.$host.']'
            : $host;
        if (443 !== $port) {
            $authority .= ':'.$port;
        }

        $path = isset($parts['path']) && \is_string($parts['path']) && '' !== $parts['path']
            ? $parts['path']
            : '/';
        $query = isset($parts['query']) && \is_string($parts['query']) && '' !== $parts['query']
            ? '?'.$parts['query']
            : '';

        return 'https://'.$authority.$path.$query;
    }
}
