<?php

declare(strict_types=1);

namespace Chamilo\LtiBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LtiProviderResponseSubscriber implements EventSubscriberInterface
{
    private const TOKEN_PARAM = 'lti_provider_token';
    private const LAUNCH_ID_PARAM = 'lti_launch_id';
    private const SESSION_CONTEXT_KEY = '_ltiProvider';
    private const MAIN_PREFIX = '/main/';
    private const BRIDGE_SCRIPT_PATH = '/plugin/LtiProvider/assets/lti-provider-bridge.js';

    private const EXCLUDED_PREFIXES = [
        '/_wdt',
        '/_profiler',
        '/build/',
        '/bundles/',
        '/media/',
        '/favicon.ico',
        '/main/build/',
        '/main/css/',
        '/main/img/',
        '/main/image/',
        '/main/images/',
        '/main/font/',
        '/main/fonts/',
        '/main/js/',
        '/main/assets/',
        '/main/node_modules/',
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -64],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $this->getLegacyRequestPath($request);

        if (!$this->supportsRequest($path)) {
            return;
        }

        $context = $this->resolveContextFromRequest($request);

        if ([] === $context || empty($context['token'])) {
            return;
        }

        $response = $event->getResponse();
        $this->setContextCookies($request, $response, $context);

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        if (!str_contains($contentType, 'text/html')) {
            return;
        }

        $content = $response->getContent();

        if (!\is_string($content) || '' === $content) {
            return;
        }

        $updatedContent = $this->rewriteHtmlAttributes($content, $context, $request);
        $updatedContent = $this->injectBridgeLoader($updatedContent, $context);

        if ($updatedContent !== $content) {
            $response->setContent($updatedContent);
            $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
        }

        $this->logger->debug('[LtiProvider response] Launch context injected into HTML response.', [
            'path' => $path,
            'launch_id' => $context['launch_id'] ?? '',
        ]);
    }

    private function resolveContextFromRequest(Request $request): array
    {
        $token = (string) $request->query->get(self::TOKEN_PARAM, '');
        $launchId = (string) $request->query->get(self::LAUNCH_ID_PARAM, '');

        if ('' === $token) {
            $token = (string) $request->request->get(self::TOKEN_PARAM, '');
        }

        if ('' === $launchId) {
            $launchId = (string) $request->request->get(self::LAUNCH_ID_PARAM, '');
        }

        if ('' === $token) {
            $token = (string) $request->headers->get('X-Lti-Provider-Token', '');
        }

        if ('' === $launchId) {
            $launchId = (string) $request->headers->get('X-Lti-Launch-Id', '');
        }

        if ('' === $token) {
            $token = (string) $request->cookies->get(self::TOKEN_PARAM, '');
        }

        if ('' === $launchId) {
            $launchId = (string) $request->cookies->get(self::LAUNCH_ID_PARAM, '');
        }

        if (($token === '' || $launchId === '') && $request->hasSession()) {
            $storedContext = $request->getSession()->get(self::SESSION_CONTEXT_KEY);

            if (\is_array($storedContext)) {
                if ('' === $token) {
                    $token = (string) ($storedContext['token'] ?? '');
                }

                if ('' === $launchId) {
                    $launchId = (string) ($storedContext['launch_id'] ?? '');
                }
            }
        }

        if ('' === $token) {
            return [];
        }

        return [
            'token' => $token,
            'launch_id' => $launchId,
        ];
    }

    private function setContextCookies(Request $request, Response $response, array $context): void
    {
        $isSecure = $request->isSecure();
        $sameSite = $isSecure ? Cookie::SAMESITE_NONE : Cookie::SAMESITE_LAX;

        $response->headers->setCookie(
            Cookie::create(self::TOKEN_PARAM)
                ->withValue((string) $context['token'])
                ->withPath('/main')
                ->withHttpOnly(true)
                ->withSecure($isSecure)
                ->withSameSite($sameSite)
        );

        if (!empty($context['launch_id'])) {
            $response->headers->setCookie(
                Cookie::create(self::LAUNCH_ID_PARAM)
                    ->withValue((string) $context['launch_id'])
                    ->withPath('/main')
                    ->withHttpOnly(true)
                    ->withSecure($isSecure)
                    ->withSameSite($sameSite)
            );
        }
    }

    private function rewriteHtmlAttributes(string $html, array $context, Request $request): string
    {
        $pattern = '/(<(?:a|form|iframe)\b[^>]*?\s(?:href|action|src)=)(["\'])(.*?)\2/si';

        $rewritten = preg_replace_callback(
            $pattern,
            function (array $matches) use ($context, $request): string {
                $updatedUrl = $this->appendLaunchContextToUrl($matches[3], $context, $request);

                return $matches[1].$matches[2].htmlspecialchars($updatedUrl, ENT_QUOTES | ENT_HTML5).$matches[2];
            },
            $html
        );

        return \is_string($rewritten) ? $rewritten : $html;
    }

    private function injectBridgeLoader(string $html, array $context): string
    {
        if (str_contains($html, 'data-lti-provider-bridge-loader="1"')) {
            return $html;
        }

        $token = json_encode(
            (string) ($context['token'] ?? ''),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        $launchId = json_encode(
            (string) ($context['launch_id'] ?? ''),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        $scriptPath = htmlspecialchars(self::BRIDGE_SCRIPT_PATH, ENT_QUOTES | ENT_HTML5);

        $loader = <<<HTML
<script data-lti-provider-bridge-loader="1">
window.__ltiProviderContext = {
    token: {$token},
    launchId: {$launchId}
};
</script>
<script src="{$scriptPath}" data-lti-provider-bridge-loader="1"></script>
HTML;

        if (preg_match('~</body>~i', $html)) {
            $updated = preg_replace('~</body>~i', $loader.'</body>', $html, 1);

            return \is_string($updated) ? $updated : ($html.$loader);
        }

        if (preg_match('~</head>~i', $html)) {
            $updated = preg_replace('~</head>~i', $loader.'</head>', $html, 1);

            return \is_string($updated) ? $updated : ($html.$loader);
        }

        return $html.$loader;
    }

    private function appendLaunchContextToUrl(string $url, array $context, Request $request): string
    {
        $decodedUrl = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);

        if ($this->isSkippableUrl($decodedUrl)) {
            return $url;
        }

        $parts = parse_url($decodedUrl);

        if (false === $parts) {
            return $url;
        }

        $path = (string) ($parts['path'] ?? '');

        if (!$this->shouldRewriteUrl($decodedUrl, $path, $request)) {
            return $url;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);

        $query[self::TOKEN_PARAM] = (string) $context['token'];

        if (!empty($context['launch_id'])) {
            $query[self::LAUNCH_ID_PARAM] = (string) $context['launch_id'];
        }

        $parts['query'] = http_build_query($query);

        return $this->unparseUrl($parts);
    }

    private function shouldRewriteUrl(string $originalUrl, string $path, Request $request): bool
    {
        if ('' !== $path && str_starts_with($path, self::MAIN_PREFIX)) {
            return true;
        }

        if (str_starts_with($originalUrl, '?')) {
            return true;
        }

        $hasScheme = 1 === preg_match('~^[a-z][a-z0-9+\-.]*:~i', $originalUrl);

        if ($hasScheme) {
            $host = (string) (parse_url($originalUrl, PHP_URL_HOST) ?: '');

            return '' !== $host
                && $host === $request->getHost()
                && str_starts_with($path, self::MAIN_PREFIX);
        }

        if (str_starts_with($originalUrl, '//')) {
            $host = (string) (parse_url($request->getScheme().':'.$originalUrl, PHP_URL_HOST) ?: '');

            return '' !== $host && $host === $request->getHost();
        }

        return !str_starts_with($originalUrl, '/');
    }

    private function isSkippableUrl(string $url): bool
    {
        if ('' === $url) {
            return true;
        }

        $lower = strtolower($url);

        return
            str_starts_with($lower, '#') ||
            str_starts_with($lower, 'javascript:') ||
            str_starts_with($lower, 'mailto:') ||
            str_starts_with($lower, 'tel:') ||
            str_starts_with($lower, 'data:') ||
            str_starts_with($lower, 'blob:');
    }

    private function unparseUrl(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':'.$parts['pass'] : '';
        $auth = '' !== $user ? $user.$pass.'@' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) && '' !== $parts['query'] ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $scheme.$auth.$host.$port.$path.$query.$fragment;
    }

    private function supportsRequest(string $path): bool
    {
        if ('' === $path || '/' === $path) {
            return false;
        }

        foreach (self::EXCLUDED_PREFIXES as $excludedPrefix) {
            if (str_starts_with($path, $excludedPrefix)) {
                return false;
            }
        }

        if (!str_starts_with($path, self::MAIN_PREFIX)) {
            return false;
        }

        return 1 === preg_match('~\.php(?:/.*)?$~i', $path);
    }

    private function getLegacyRequestPath(Request $request): string
    {
        $path = $request->getPathInfo();

        if ('' === $path || '/' === $path) {
            $requestUri = (string) $request->server->get('REQUEST_URI', '');
            $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?: '/');
        }

        return $path;
    }
}
