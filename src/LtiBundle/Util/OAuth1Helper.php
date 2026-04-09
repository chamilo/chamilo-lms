<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Util;

final class OAuth1Helper
{
    public static function buildSignedPostParams(
        string $url,
        array $params,
        string $consumerKey,
        string $consumerSecret,
        string $tokenSecret = ''
    ): array {
        $oauthParams = [
            'oauth_consumer_key' => $consumerKey,
            'oauth_nonce' => self::generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_version' => '1.0',
        ];

        $signedParams = array_merge($params, $oauthParams);
        $signedParams['oauth_signature'] = self::buildSignature(
            'POST',
            $url,
            $signedParams,
            $consumerSecret,
            $tokenSecret
        );

        return $signedParams;
    }

    private static function buildSignature(
        string $method,
        string $url,
        array $params,
        string $consumerSecret,
        string $tokenSecret = ''
    ): string {
        $baseString = self::buildBaseString($method, $url, $params);
        $signingKey = self::encode($consumerSecret).'&'.self::encode($tokenSecret);

        return base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
    }

    private static function buildBaseString(string $method, string $url, array $params): string
    {
        $normalizedUrl = self::normalizeUrl($url);
        $normalizedParams = self::normalizeParameters($url, $params);

        return strtoupper($method)
            .'&'.self::encode($normalizedUrl)
            .'&'.self::encode($normalizedParams);
    }

    private static function normalizeParameters(string $url, array $params): string
    {
        $queryParams = [];
        $query = (string) parse_url($url, PHP_URL_QUERY);

        if ('' !== $query) {
            parse_str($query, $queryParams);
        }

        $allParams = array_merge($queryParams, $params);
        unset($allParams['oauth_signature']);

        $pairs = [];

        foreach ($allParams as $key => $value) {
            if (is_array($value)) {
                sort($value, SORT_STRING);

                foreach ($value as $item) {
                    $pairs[] = [self::encode((string) $key), self::encode((string) $item)];
                }

                continue;
            }

            $pairs[] = [self::encode((string) $key), self::encode((string) $value)];
        }

        usort(
            $pairs,
            static function (array $a, array $b): int {
                if ($a[0] === $b[0]) {
                    return $a[1] <=> $b[1];
                }

                return $a[0] <=> $b[0];
            }
        );

        $chunks = [];
        foreach ($pairs as [$key, $value]) {
            $chunks[] = $key.'='.$value;
        }

        return implode('&', $chunks);
    }

    private static function normalizeUrl(string $url): string
    {
        $parts = parse_url($url);

        $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $path = (string) ($parts['path'] ?? '/');

        $includePort = null !== $port
            && !(('http' === $scheme && 80 === $port) || ('https' === $scheme && 443 === $port));

        return $scheme.'://'.$host.($includePort ? ':'.$port : '').$path;
    }

    private static function encode(string $value): string
    {
        return str_replace('%7E', '~', rawurlencode($value));
    }

    private static function generateNonce(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable) {
            return sha1(uniqid((string) mt_rand(), true));
        }
    }
}
