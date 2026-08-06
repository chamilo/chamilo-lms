<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const PHP_URL_PATH;

/**
 * Central CSRF gate for every state-changing API Platform operation and legacy
 * AJAX endpoint.
 *
 * API Platform ships no CSRF mechanism of its own, so protection used to be
 * opt-in: each State provider emitted a token and each processor validated it
 * by hand. This listener replaces that with a single check, backed by Symfony's
 * stateless SameOriginCsrfTokenManager, which accepts a request when either:
 *
 * - the origin matches (Sec-Fetch-Site, Origin or Referer), or
 * - a double-submit token is present (the 'csrf-token' header, plus the
 *   matching '__Host-csrf-token_<token>' cookie the clients set).
 *
 * Since the manager's getToken() returns a placeholder rather than a secret,
 * nothing has to be emitted server-side: the placeholder is enough to trigger
 * the origin check, and clients that also send the header get both checks. That
 * keeps the whole thing free of session state, so a full page reload — which
 * remounts the Vue app — has nothing to restore.
 *
 * Runs at kernel.request priority 9: after RouterListener (32) has resolved the
 * API Platform route attributes, and before the firewall (8), so a forged
 * request is rejected before the request pays for authentication and course
 * context resolution.
 *
 * Note that API Platform negotiates the format earlier still, in
 * AddFormatListener (priority 28): a write request with an unsupported content
 * type is answered with 415 and never reaches this check.
 */
final class CsrfProtectionListener
{
    /**
     * Must be listed under framework.csrf_protection.stateless_token_ids for
     * the stateless manager to handle it instead of the session-backed one.
     */
    public const string TOKEN_ID = 'chamilo_request';

    /**
     * Header carrying the double-submit token, matching the cookie name
     * configured in framework.csrf_protection.cookie_name.
     */
    public const string TOKEN_HEADER = 'csrf-token';

    private const string LEGACY_AJAX_PATH_PREFIX = '/main/inc/ajax/';

    /**
     * Headers whose presence means the client authenticates with credentials
     * it has to set deliberately, which a cross-site page cannot do: sending
     * any of them requires a CORS preflight the browser would have to pass.
     *
     * X-Chamilo-Api-Key belongs here even though WebserviceApiKeyAuthenticator
     * is not wired into a firewall right now, so that wiring it back in never
     * silently starts rejecting server-to-server integrations.
     */
    private const array CREDENTIAL_HEADERS = ['Authorization', 'X-Chamilo-Api-Key'];

    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly LoggerInterface $logger,
        private readonly bool $enforce,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->isMethodSafe() || !$this->isProtected($request)) {
            return;
        }

        // The submitted value only feeds the double-submit check. Falling back
        // to the cookie name is what tells the manager no double-submit was
        // attempted, leaving the origin check as the sole criterion.
        $submitted = (string) $request->headers->get(self::TOKEN_HEADER, self::TOKEN_HEADER);

        if ($this->csrfTokenManager->isTokenValid(new CsrfToken(self::TOKEN_ID, $submitted))) {
            return;
        }

        if (!$this->enforce) {
            // The point of log-only mode is deciding whether enforcing is safe,
            // which means being able to tell *who* would have been rejected --
            // hence the user agent and client IP alongside the header flags.
            $this->logger->warning('CSRF validation would have rejected this request.', [
                'path' => $this->getRequestPath($request),
                'method' => $request->getMethod(),
                'client_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'has_origin' => $request->headers->has('Origin'),
                'has_referer' => $request->headers->has('Referer'),
                'has_sec_fetch_site' => $request->headers->has('Sec-Fetch-Site'),
                'has_csrf_header' => $request->headers->has(self::TOKEN_HEADER),
            ]);

            return;
        }

        throw new AccessDeniedHttpException('The security token is invalid.');
    }

    private function isProtected(Request $request): bool
    {
        // No ambient session cookie means no CSRF surface: a cross-site page
        // cannot replay credentials it has to set explicitly. This is what
        // keeps Bearer/JWT clients, mobile apps and integrations untouched.
        //
        if (!$request->hasPreviousSession()) {
            return false;
        }

        // Credential headers must be tested for content, not presence: Apache
        // + PHP-FPM setups forward Authorization as an empty string (see the
        // SetEnvIf note in the installation docs), so has() alone would report
        // every request as credential-bearing and silently disable the check.
        foreach (self::CREDENTIAL_HEADERS as $header) {
            if ('' !== trim((string) $request->headers->get($header, ''))) {
                return false;
            }
        }

        if (str_starts_with($this->getRequestPath($request), self::LEGACY_AJAX_PATH_PREFIX)) {
            return true;
        }

        return $this->isProtectedApiOperation($request);
    }

    /**
     * Legacy pages are executed directly by the web server and only then boot
     * the kernel, so Request::createFromGlobals() folds the script name into
     * the base path and leaves an empty path info. Falling back to REQUEST_URI
     * is how LtiProviderRequestSubscriber::getLegacyRequestPath() deals with
     * the same problem.
     */
    private function getRequestPath(Request $request): string
    {
        $path = $request->getPathInfo();

        if ('' !== $path && '/' !== $path) {
            return $path;
        }

        $requestUri = (string) $request->server->get('REQUEST_URI', '');

        return (string) (parse_url($requestUri, PHP_URL_PATH) ?: '/');
    }

    /**
     * Reads _api_resource_class rather than _api_operation because the latter
     * is only set later on, by OperationRequestInitiatorTrait. Both
     * _api_resource_class and _api_operation_name are route defaults set by
     * ApiLoader, so they are available as soon as the router has run.
     */
    private function isProtectedApiOperation(Request $request): bool
    {
        $resourceClass = $request->attributes->get('_api_resource_class');

        if (!\is_string($resourceClass) || '' === $resourceClass) {
            return false;
        }

        $operationName = $request->attributes->get('_api_operation_name');

        try {
            $operation = $this->resourceMetadataCollectionFactory
                ->create($resourceClass)
                ->getOperation(\is_string($operationName) ? $operationName : null)
            ;
        } catch (OperationNotFoundException) {
            // Let API Platform report the unknown operation itself.
            return false;
        }

        // Opt-out for operations called by non-browser clients, declared as
        // extraProperties: ['csrf' => false] on the operation.
        return false !== ($operation->getExtraProperties()['csrf'] ?? true);
    }
}
