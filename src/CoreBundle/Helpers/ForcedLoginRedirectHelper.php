<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Decides whether an anonymous navigation goes to the OAuth2 provider that declares
 * force_redirect, instead of reaching the login page.
 *
 * Shared by AuthenticationEntryPoint (protected pages) and ForcedLoginRedirectListener
 * (public ones, such as / and /login).
 */
readonly class ForcedLoginRedirectHelper
{
    // Reaches the local login form anyway, for when the provider is unreachable.
    public const string ESCAPE_PARAMETER = 'skipForcedRedirect';

    public const string SESSION_KEY = 'chamilo.skip_forced_login_redirect';

    // Never redirected: they carry the handshake, end the session, or answer a machine.
    private const array ALWAYS_SKIPPED_PREFIXES = [
        '/connect/',
        '/logout',
        '/api/',
        '/oauth/',
        '/scim/',
        '/mcp',
        '/.well-known/',
    ];

    public function __construct(
        private AuthenticationConfigHelper $authenticationConfigHelper,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {}

    public function resolveRedirectUrl(Request $request): ?string
    {
        // Cheapest guard first: it reads a parameter, while the lookup below costs a query.
        if (!$this->authenticationConfigHelper->hasForcedRedirectProviderDeclared()) {
            return null;
        }

        // A redirected write comes back as a GET without its body.
        if (!$request->isMethod(Request::METHOD_GET)) {
            return null;
        }

        // An XHR caller would read the provider's HTML as a valid payload.
        if (RequestExpectsJsonHelper::expectsJson($request)) {
            return null;
        }

        // Only a page the browser displays may move, never an image or a download.
        if (!$this->isDocumentNavigation($request)) {
            return null;
        }

        if ($this->captureEscapeParameter($request)) {
            return null;
        }

        $provider = $this->authenticationConfigHelper->getForcedRedirectProvider();

        if (null === $provider) {
            return null;
        }

        $path = $request->getPathInfo();

        foreach (self::ALWAYS_SKIPPED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return null;
            }
        }

        $uri = $request->getRequestUri();

        foreach ($provider['skip'] as $fragment) {
            if (str_contains($uri, $fragment)) {
                return null;
            }
        }

        $route = \sprintf('chamilo.oauth2_%s_start', $provider['name']);

        try {
            return $this->urlGenerator->generate($route);
        } catch (RouteNotFoundException $exception) {
            // Unknown provider name. Keep the login page reachable instead of a 500 everywhere.
            $this->logger->error(
                'force_redirect names an unknown OAuth2 provider, ignoring it.',
                ['provider' => $provider['name'], 'route' => $route, 'exception' => $exception]
            );

            return null;
        }
    }

    /**
     * Tells whether the visitor asked for the local login form.
     */
    public function isEscapeActive(Request $request): bool
    {
        // The parameter belongs to force_redirect. It must not undo force_as_login_method.
        if (!$this->authenticationConfigHelper->hasForcedRedirectProviderDeclared()) {
            return false;
        }

        $requested = $request->query->get(self::ESCAPE_PARAMETER);

        if (null !== $requested) {
            return $this->readEscapeValue($requested);
        }

        return $request->hasSession() && true === $request->getSession()->get(self::SESSION_KEY);
    }

    /**
     * The session keeps the answer, because the SPA then navigates without a page load.
     */
    private function captureEscapeParameter(Request $request): bool
    {
        $requested = $request->query->get(self::ESCAPE_PARAMETER);

        if (null === $requested) {
            return $this->isEscapeActive($request);
        }

        $active = $this->readEscapeValue($requested);

        if ($request->hasSession()) {
            $session = $request->getSession();

            if ($active) {
                $session->set(self::SESSION_KEY, true);
            } else {
                $session->remove(self::SESSION_KEY);
            }
        }

        return $active;
    }

    private function readEscapeValue(mixed $value): bool
    {
        return !\in_array(strtolower((string) $value), ['0', 'false', 'no', 'off'], true);
    }

    /**
     * Sec-Fetch-Dest is exact and every current browser sends it. Accept is the fallback.
     */
    private function isDocumentNavigation(Request $request): bool
    {
        $destination = (string) $request->headers->get('Sec-Fetch-Dest', '');

        if ('' !== $destination) {
            return 'document' === $destination;
        }

        return str_contains((string) $request->headers->get('Accept'), 'text/html');
    }
}
