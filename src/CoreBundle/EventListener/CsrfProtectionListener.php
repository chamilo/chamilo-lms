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
 * stateless SameOriginCsrfTokenManager, which verifies that the request comes
 * from this site through Sec-Fetch-Site, Origin or Referer.
 *
 * Nothing is emitted server-side and nothing is expected from the client: the
 * manager's getToken() returns a placeholder rather than a secret, and passing
 * that placeholder is what asks it for the origin check. Browsers attach those
 * headers to every state-changing request on their own, so no caller has to
 * cooperate — which matters here, where writes go out through axios, jQuery,
 * Uppy, PrimeVue uploaders and plain fetch.
 *
 * The manager also supports a double-submit cookie/header pair as a second
 * barrier. It is deliberately NOT used: once one request validates that way,
 * Symfony's anti-downgrade rule requires it from every later request in the
 * session, so a single caller that does not send it — an uploader, a plugin —
 * starts failing intermittently and only after some other request has "armed"
 * the session. Verifying the origin costs nothing and cannot be forged by a
 * cross-site page.
 *
 * Being free of session state also means a full page reload — which remounts
 * the Vue app — has nothing to restore.
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
     * Value handed to the manager to request an origin check. It has to equal
     * framework.csrf_protection.cookie_name, which is how SameOriginCsrfToken-
     * Manager recognises "no double-submit was attempted".
     */
    public const string ORIGIN_CHECK_TOKEN = 'csrf-token';

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

        if ($this->csrfTokenManager->isTokenValid(new CsrfToken(self::TOKEN_ID, self::ORIGIN_CHECK_TOKEN))) {
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
