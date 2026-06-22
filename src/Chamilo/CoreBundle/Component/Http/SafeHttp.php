<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Http;

/**
 * Shared SSRF guard for every server-side fetch of a user-supplied URL.
 *
 * Chamilo issues outbound HTTP requests on behalf of users in several places
 * (mPDF remote assets, the LTI cartridge auto-detect, ...). Without filtering,
 * an attacker can point those fetches at loopback/private/reserved hosts or the
 * cloud metadata endpoint (169.254.169.254) to scan the internal network or
 * steal instance IAM credentials (CWE-918, SSRF).
 *
 * This class centralises the validation logic: resolve the host, reject any
 * address inside a private/reserved/loopback/link-local range, and expose cURL
 * options that pin the connection to the validated IP (defeating DNS rebinding)
 * while forbidding non-HTTP schemes and redirects.
 *
 * The 1.11.x branch ships Symfony 3.4, which has no NoPrivateNetworkHttpClient,
 * so the validation is implemented here directly.
 */
final class SafeHttp
{
    /**
     * Validates the URL and returns a single safe IP to connect to, or null if
     * the target must be blocked.
     *
     * A target is blocked when: the scheme is not http(s), the host is missing,
     * the host does not resolve, or ANY resolved address falls inside a
     * private/reserved/loopback/link-local range (e.g. 127.0.0.0/8,
     * 169.254.0.0/16, 10/8, ::1, fc00::/7).
     *
     * @param string $uri
     *
     * @return string|null
     */
    public static function resolveSafeIp($uri)
    {
        $parts = parse_url($uri);

        if (false === $parts || empty($parts["host"])) {
            return null;
        }

        $scheme = strtolower(isset($parts["scheme"]) ? $parts["scheme"] : "");
        if (!in_array($scheme, ["http", "https"], true)) {
            return null;
        }

        // parse_url keeps IPv6 literals wrapped in brackets.
        $host = trim($parts["host"], "[]");

        $addresses = self::resolveHost($host);
        if (empty($addresses)) {
            return null;
        }

        $safeIp = null;
        foreach ($addresses as $address) {
            if (!self::isPublicIp($address)) {
                // A single private/reserved hit blocks the whole request.
                return null;
            }
            if (null === $safeIp) {
                $safeIp = $address;
            }
        }

        return $safeIp;
    }

    /**
     * Returns the cURL options that harden an outbound request against SSRF:
     * restrict the protocol to http(s), forbid following redirects (a 30x could
     * bounce to an internal host after validation) and pin the connection to the
     * already-validated IP to prevent a DNS-rebinding race.
     *
     * @param string $uri the validated URL
     * @param string $ip  the safe IP returned by resolveSafeIp()
     *
     * @return array
     */
    public static function secureCurlOptions($uri, $ip)
    {
        $parts = parse_url($uri);
        $host = trim($parts["host"] ?? "", "[]");
        $scheme = strtolower($parts["scheme"] ?? "http");
        $port = (int) ($parts["port"] ?? ("https" === $scheme ? 443 : 80));

        $options = [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_RESOLVE => ["$host:$port:$ip"],
        ];

        if (defined("CURLOPT_PROTOCOLS") && defined("CURLPROTO_HTTP") && defined("CURLPROTO_HTTPS")) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        return $options;
    }

    /**
     * Resolves a host (or returns it as-is if already an IP literal) into a
     * list of IPv4/IPv6 addresses.
     *
     * @param string $host
     *
     * @return string[]
     */
    public static function resolveHost($host)
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $addresses = [];

        $ipv4 = @gethostbynamel($host);
        if (is_array($ipv4)) {
            $addresses = $ipv4;
        }

        $records = @dns_get_record($host, DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                if (!empty($record["ipv6"])) {
                    $addresses[] = $record["ipv6"];
                }
            }
        }

        return array_values(array_unique($addresses));
    }

    /**
     * @param string $ip
     *
     * @return bool
     */
    public static function isPublicIp($ip)
    {
        return false !== filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
