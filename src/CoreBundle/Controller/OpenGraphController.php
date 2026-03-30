<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use DOMDocument;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_VALIDATE_IP;
use const LIBXML_NOERROR;
use const LIBXML_NONET;
use const LIBXML_NOWARNING;
use const PHP_URL_HOST;

#[Route('/social-network')]
#[IsGranted('ROLE_USER')]
class OpenGraphController extends AbstractController
{
    public function __construct(
        private readonly RateLimiterFactory $opengraphFetchLimiter,
    ) {}

    /**
     * Domains from which OpenGraph metadata may be fetched.
     * Only public, well-known content platforms are allowed.
     * Add entries here to extend the list.
     */
    private const ALLOWED_DOMAINS = [
        'youtube.com',
        'youtu.be',
        'vimeo.com',
        'dailymotion.com',
        'twitter.com',
        'x.com',
        'github.com',
        'linkedin.com',
        'wikipedia.org',
        'medium.com',
        'reddit.com',
        'stackoverflow.com',
        'slideshare.net',
        'ted.com',
        'soundcloud.com',
        'spotify.com',
        'flickr.com',
        'instagram.com',
        'facebook.com',
    ];

    #[Route('/opengraph', name: 'social_opengraph_fetch', methods: ['POST'])]
    public function fetch(Request $request): JsonResponse
    {
        $limiter = $this->opengraphFetchLimiter->create($request->getClientIp());
        if (false === $limiter->consume()->isAccepted()) {
            return $this->json(['error' => 'Too many requests.'], 429);
        }

        $url = '';
        $contentType = $request->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            $payload = json_decode((string) $request->getContent(), true);
            $url = trim((string) ($payload['url'] ?? ''));
        } else {
            $url = trim((string) $request->request->get('url', ''));
        }

        if ('' === $url) {
            return $this->json(['error' => 'No URL provided.'], 400);
        }

        $parsed = parse_url($url);
        if (empty($parsed['scheme']) || !\in_array($parsed['scheme'], ['http', 'https'], true)) {
            return $this->json(['error' => 'Invalid URL scheme.'], 400);
        }

        $host = strtolower($parsed['host'] ?? '');
        if ('' === $host) {
            return $this->json(['error' => 'Invalid URL.'], 400);
        }

        if (!$this->isDomainAllowed($host)) {
            return $this->json(['error' => 'Domain not allowed.'], 422);
        }

        if (!$this->isUrlSafe($host)) {
            return $this->json(['error' => 'URL blocked by security policy.'], 403);
        }

        $og = $this->fetchOpenGraphData($url);

        if (null === $og) {
            return $this->json(['error' => 'Could not fetch metadata.'], 422);
        }

        return $this->json($og, 200, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function isDomainAllowed(string $host): bool
    {
        $normalized = preg_replace('/^www\./', '', $host);

        foreach (self::ALLOWED_DOMAINS as $allowed) {
            $allowedNorm = preg_replace('/^www\./', '', strtolower($allowed));
            if ($normalized === $allowedNorm || str_ends_with($normalized, '.'.$allowedNorm)) {
                return true;
            }
        }

        return false;
    }

    private function isUrlSafe(string $host): bool
    {
        $ip = gethostbyname($host);
        if ($ip === $host) {
            return false;
        }

        if (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        return true;
    }

    private function fetchOpenGraphData(string $url): ?array
    {
        $client = new Client();

        try {
            $response = $client->request('GET', $url, [
                'timeout' => 8,
                'connect_timeout' => 5,
                'allow_redirects' => false,
                'headers' => [
                    'User-Agent' => 'Chamilo OpenGraph Fetcher',
                    'Accept' => 'text/html',
                ],
            ]);

            $statusCode = $response->getStatusCode();

            // Follow redirects manually with safety checks (max 3)
            $redirectCount = 0;
            while (\in_array($statusCode, [301, 302, 303, 307, 308], true) && $redirectCount < 3) {
                $location = $response->getHeaderLine('Location');
                if ('' === $location) {
                    break;
                }

                // Resolve relative redirects against the current URL
                if (!preg_match('#^https?://#i', $location)) {
                    $baseParsed = parse_url($url);
                    $base = ($baseParsed['scheme'] ?? 'https').'://'.($baseParsed['host'] ?? '');
                    if (str_starts_with($location, '//')) {
                        $location = ($baseParsed['scheme'] ?? 'https').':'.$location;
                    } elseif (str_starts_with($location, '/')) {
                        $location = $base.$location;
                    } else {
                        $location = $base.'/'.$location;
                    }
                }

                // Validate redirect target
                $redirectParsed = parse_url($location);
                $redirectScheme = strtolower($redirectParsed['scheme'] ?? '');
                $redirectHost = strtolower($redirectParsed['host'] ?? '');
                if (!\in_array($redirectScheme, ['http', 'https'], true)) {
                    return null;
                }
                if ('' === $redirectHost || !$this->isDomainAllowed($redirectHost) || !$this->isUrlSafe($redirectHost)) {
                    return null;
                }

                $response = $client->request('GET', $location, [
                    'timeout' => 8,
                    'connect_timeout' => 5,
                    'allow_redirects' => false,
                    'headers' => [
                        'User-Agent' => 'Chamilo OpenGraph Fetcher',
                        'Accept' => 'text/html',
                    ],
                ]);
                $statusCode = $response->getStatusCode();
                $redirectCount++;
            }

            if (200 !== $statusCode) {
                return null;
            }

            $contentType = $response->getHeaderLine('Content-Type');
            if (!str_contains($contentType, 'text/html')) {
                return null;
            }

            $body = (string) $response->getBody();
            // Limit to first 100KB to avoid processing huge pages
            $body = substr($body, 0, 102400);

            return $this->parseOpenGraphTags($body, $url);
        } catch (Throwable $e) {
            return null;
        }
    }

    private function parseOpenGraphTags(string $html, string $url): ?array
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $tags = [];
        $metas = $doc->getElementsByTagName('meta');

        foreach ($metas as $meta) {
            $property = $meta->getAttribute('property');
            $content = $meta->getAttribute('content');

            if ('' !== $property && str_starts_with($property, 'og:') && '' !== $content) {
                $key = substr($property, 3);
                $tags[$key] = $content;
            }

            // Fallback: name-based meta tags
            $name = $meta->getAttribute('name');
            if ('description' === $name && !isset($tags['description']) && '' !== $content) {
                $tags['description'] = $content;
            }
        }

        // Fallback title: og:site_name → <title> tag
        if (!isset($tags['title'])) {
            if (!empty($tags['site_name'])) {
                $tags['title'] = $tags['site_name'];
            } else {
                $titleNodes = $doc->getElementsByTagName('title');
                if ($titleNodes->length > 0) {
                    $tags['title'] = trim($titleNodes->item(0)->textContent);
                }
            }
        }

        if (empty($tags['title']) && empty($tags['description'])) {
            return null;
        }

        $parsed = parse_url($url);
        $domain = $parsed['host'] ?? '';

        return [
            'title' => $this->sanitize($tags['title'] ?? ''),
            'description' => $this->sanitize(mb_substr($tags['description'] ?? '', 0, 300)),
            'image' => $this->sanitizeImageUrl($tags['image'] ?? ''),
            'url' => $this->sanitizeUrl($url),
            'domain' => $this->sanitize($domain),
        ];
    }

    private function sanitize(string $text): string
    {
        return htmlspecialchars(strip_tags($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function sanitizeUrl(string $url): string
    {
        if ('' === $url) {
            return '';
        }

        $parsed = parse_url($url);
        if (empty($parsed['scheme']) || !\in_array($parsed['scheme'], ['http', 'https'], true)) {
            return '';
        }

        return $url;
    }

    /**
     * Sanitize og:image URLs: allow any public domain (CDNs are common)
     * but block private/reserved IPs and non-HTTP schemes.
     */
    private function sanitizeImageUrl(string $url): string
    {
        $url = $this->sanitizeUrl($url);
        if ('' === $url) {
            return '';
        }

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        if ('' === $host) {
            return '';
        }

        // Block private/reserved IPs to prevent internal network probing via <img>
        $ip = gethostbyname($host);
        if ($ip !== $host && false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return '';
        }

        return $url;
    }
}
