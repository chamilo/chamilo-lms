<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\EventListener;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Chamilo\CoreBundle\EventListener\CsrfProtectionListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Guards the central CSRF gate that replaced the per-endpoint manual checks.
 *
 * The point of each case is *why* a request is or is not validated: requests
 * without a session cookie cannot be forged cross-site, so validating them
 * would only break non-browser clients; requests that do carry one must never
 * slip through, whichever route they target.
 */
final class CsrfProtectionListenerTest extends TestCase
{
    private const string RESOURCE_CLASS = 'App\Entity\Dummy';
    private const string OPERATION_NAME = 'dummy_post';

    private CsrfTokenManagerInterface&MockObject $csrfTokenManager;

    protected function setUp(): void
    {
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
    }

    public function testSafeMethodIsNeverValidated(): void
    {
        // A GET cannot be a CSRF write, and validating it would reject
        // legitimate top-level navigations that carry no Origin.
        $this->csrfTokenManager->expects(self::never())->method('isTokenValid');

        $this->listen($this->apiRequest('GET'));
    }

    public function testSubRequestIsIgnored(): void
    {
        $this->csrfTokenManager->expects(self::never())->method('isTokenValid');

        $this->listen($this->apiRequest('POST'), HttpKernelInterface::SUB_REQUEST);
    }

    public function testRequestWithoutSessionCookieIsNotValidated(): void
    {
        // No ambient credentials means no CSRF surface. Validating here would
        // break mobile apps, integrations and the Bearer-based test client.
        $this->csrfTokenManager->expects(self::never())->method('isTokenValid');

        $this->listen($this->apiRequest('POST', withSession: false));
    }

    public function testBearerAuthenticatedRequestIsNotValidated(): void
    {
        $request = $this->apiRequest('POST');
        $request->headers->set('Authorization', 'Bearer some.jwt.token');

        $this->csrfTokenManager->expects(self::never())->method('isTokenValid');

        $this->listen($request);
    }

    public function testWebserviceApiKeyIsTreatedAsExplicitCredentials(): void
    {
        // Server-to-server integrations authenticating with X-Chamilo-Api-Key
        // set a header a cross-site page cannot forge, so they carry no CSRF
        // risk and must not start failing if that authenticator gets wired in.
        $request = $this->apiRequest('POST');
        $request->headers->set('X-Chamilo-Api-Key', 'an-integration-key');

        $this->csrfTokenManager->expects(self::never())->method('isTokenValid');

        $this->listen($request);
    }

    public function testEmptyAuthorizationHeaderStillGetsValidated(): void
    {
        // Apache + PHP-FPM forwards Authorization as an empty string, so
        // treating its mere presence as "this client sends credentials" would
        // silently disable the whole check on those deployments.
        $request = $this->apiRequest('POST');
        $request->headers->set('Authorization', '');

        $this->csrfTokenManager->expects(self::once())->method('isTokenValid')->willReturn(true);

        $this->listen($request);
    }

    public function testLegacyPageWithNoResolvedRouteIsNotValidated(): void
    {
        // Legacy page form posts keep their own FormValidator token; the
        // listener must not reach into them. The web server executes them and
        // only then boots the kernel, so no route is ever resolved — that
        // absence is what keeps them out of scope.
        $this->csrfTokenManager->expects(self::never())->method('isTokenValid');

        $this->listen($this->sessionRequest('POST', '/main/admin/user_list.php', null));
    }

    public function testRoutedControllerOutsideApiIsValidated(): void
    {
        // Scope is default-protected: a resolved route is guarded whatever its
        // prefix. 136 write routes live outside /api — /admin, /social-network,
        // /resources and friends — and an allowlist of prefixes would leave
        // every new one silently unguarded.
        $this->csrfTokenManager->expects(self::once())->method('isTokenValid')->willReturn(true);

        $this->listen($this->sessionRequest('POST', '/resources/lp/1/advanced-access/user'));
    }

    public function testExcludedRouteIsNotValidated(): void
    {
        // LTI Deep Linking returns the user through a cross-site form post the
        // origin check would legitimately reject.
        $this->csrfTokenManager->expects(self::never())->method('isTokenValid');

        $this->listen($this->sessionRequest('POST', '/courses/1/lti/item_return', 'chamilo_lti_return_item'));
    }

    public function testPlainControllerUnderApiIsValidated(): void
    {
        // 246 routes under /api are plain Symfony controllers rather than API
        // Platform operations — ticket admin, announcements, translations. They
        // carry no _api_resource_class, and skipping them left exactly the
        // endpoints that used to hand-roll a CSRF check unguarded.
        $this->csrfTokenManager->expects(self::once())->method('isTokenValid')->willReturn(true);

        $this->listen($this->sessionRequest('POST', '/api/ticket/admin/projects'));
    }

    public function testPlainControllerUnderApiCannotOptOut(): void
    {
        // Only API Platform operations can opt out, since only they have
        // metadata to declare it on. A controller route must not inherit an
        // opt-out from some unrelated resource.
        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);

        $this->listen(
            $this->sessionRequest('POST', '/api/ticket/admin/projects'),
            enforce: true,
            extraProperties: ['csrf' => false]
        );
    }

    public function testOperationOptedOutIsNotValidated(): void
    {
        $this->csrfTokenManager->expects(self::never())->method('isTokenValid');

        $this->listen($this->apiRequest('POST'), extraProperties: ['csrf' => false]);
    }

    public function testValidTokenLetsTheRequestThrough(): void
    {
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $this->expectNotToPerformAssertions();

        $this->listen($this->apiRequest('POST'), enforce: true);
    }

    public function testInvalidTokenIsRejectedWhenEnforcing(): void
    {
        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);

        $this->listen($this->apiRequest('POST'), enforce: true);
    }

    public function testLegacyAjaxEndpointIsValidated(): void
    {
        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);

        $this->listen(
            $this->sessionRequest('POST', '/main/inc/ajax/course.ajax.php'),
            enforce: true
        );
    }

    public function testInvalidTokenIsOnlyLoggedWhenNotEnforcing(): void
    {
        // The log-only mode is what lets a deployment discover legitimate
        // clients that would be rejected, before anyone starts getting 403s.
        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $this->listen($this->apiRequest('POST'), logger: $logger);
    }

    public function testTheOriginCheckIsWhatGetsRequested(): void
    {
        // Passing the placeholder is how SameOriginCsrfTokenManager is told no
        // double-submit was attempted, leaving the origin check in charge. The
        // listener must never derive this from a client-supplied header: a
        // caller that omitted it would then be rejected once the session had
        // validated through double-submit somewhere else.
        $request = $this->apiRequest('POST');
        $request->headers->set('csrf-token', 'a-header-the-listener-must-ignore');

        $this->csrfTokenManager
            ->expects(self::once())
            ->method('isTokenValid')
            ->with(self::callback(
                fn (CsrfToken $token): bool => CsrfProtectionListener::ORIGIN_CHECK_TOKEN === $token->getValue()
                    && CsrfProtectionListener::TOKEN_ID === $token->getId()
            ))
            ->willReturn(true)
        ;

        $this->listen($request);
    }

    private function listen(
        Request $request,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
        bool $enforce = false,
        ?LoggerInterface $logger = null,
        array $extraProperties = [],
    ): void {
        $listener = new CsrfProtectionListener(
            $this->csrfTokenManager,
            $this->metadataFactory($extraProperties),
            $logger ?? new NullLogger(),
            $enforce
        );

        $listener->onKernelRequest(new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $requestType
        ));
    }

    private function metadataFactory(array $extraProperties): ResourceMetadataCollectionFactoryInterface
    {
        $collection = new ResourceMetadataCollection(self::RESOURCE_CLASS, [
            new ApiResource(operations: [
                self::OPERATION_NAME => new Post(extraProperties: $extraProperties),
            ]),
        ]);

        $factory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $factory->method('create')->willReturn($collection);

        return $factory;
    }

    private function apiRequest(string $method, bool $withSession = true): Request
    {
        $request = $withSession
            ? $this->sessionRequest($method, '/api/dummies')
            : Request::create('/api/dummies', $method);

        $request->attributes->set('_api_resource_class', self::RESOURCE_CLASS);
        $request->attributes->set('_api_operation_name', self::OPERATION_NAME);

        return $request;
    }

    /**
     * Builds a request carrying a session cookie, which is what makes it a
     * CSRF candidate in the first place.
     *
     * The _route attribute stands for what RouterListener sets at priority 32,
     * before this listener runs: its presence is what separates a routed
     * request from a legacy page the web server executed on its own. Pass null
     * to simulate the latter.
     */
    private function sessionRequest(string $method, string $uri, ?string $route = 'a_resolved_route'): Request
    {
        $request = Request::create($uri, $method);
        $session = new Session(new MockArraySessionStorage());

        $request->setSession($session);
        $request->cookies->set($session->getName(), 'a-session-id');

        if (null !== $route) {
            $request->attributes->set('_route', $route);
        }

        return $request;
    }
}
