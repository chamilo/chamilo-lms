<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\ForcedLoginRedirectHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Pins the contract of force_redirect, the port of the 1.11.x oauth2 plugin setting.
 *
 * Each case names the failure it prevents, because a redirect answered to the wrong
 * caller returns HTTP 200 with the provider's page, and nothing looks broken.
 */
final class ForcedLoginRedirectHelperTest extends TestCase
{
    public function testAnonymousNavigationGoesToTheForcedProvider(): void
    {
        $helper = $this->createHelper(['keycloak' => $this->providerConfig(true)]);

        $this->assertSame(
            '/connect/keycloak',
            $helper->resolveRedirectUrl($this->createNavigation('/admin'))
        );
    }

    /**
     * Without the option, the visitor must still reach the login page and choose there.
     */
    public function testUnconfiguredOptionKeepsTheVisitorOnThePlatform(): void
    {
        $helper = $this->createHelper(['keycloak' => $this->providerConfig(false)]);

        $this->assertNull($helper->resolveRedirectUrl($this->createNavigation('/admin')));
    }

    /**
     * A disabled provider authenticates nobody, so it cannot receive anybody either.
     */
    public function testDisabledProviderNeverRedirects(): void
    {
        $config = $this->providerConfig(true);
        $config['enabled'] = false;

        $helper = $this->createHelper(['keycloak' => $config]);

        $this->assertNull($helper->resolveRedirectUrl($this->createNavigation('/admin')));
    }

    /**
     * The SPA reads JSON, and would take the provider's HTML for a valid payload.
     */
    public function testJsonConsumerNeverRedirects(): void
    {
        $helper = $this->createHelper(['keycloak' => $this->providerConfig(true)]);

        $request = Request::create('/platform-config/list', 'GET');
        $request->headers->set('Accept', 'application/ld+json');

        $this->assertNull($helper->resolveRedirectUrl($request));
    }

    /**
     * An image that received the provider's page would break with no visible reason.
     */
    public function testSubresourceNeverRedirects(): void
    {
        $helper = $this->createHelper(['keycloak' => $this->providerConfig(true)]);

        $request = Request::create('/build/images/logo.png', 'GET');
        $request->headers->set('Sec-Fetch-Dest', 'image');

        $this->assertNull($helper->resolveRedirectUrl($request));
    }

    /**
     * A browser replays a redirected write as a GET, and drops its body.
     */
    public function testWriteNeverRedirects(): void
    {
        $helper = $this->createHelper(['keycloak' => $this->providerConfig(true)]);

        $request = Request::create('/login_json', 'POST');
        $request->headers->set('Accept', 'text/html');

        $this->assertNull($helper->resolveRedirectUrl($request));
    }

    /**
     * The list is how an administrator keeps a public area open.
     */
    public function testConfiguredSkipListExemptsThePath(): void
    {
        $config = $this->providerConfig(true);
        $config['skip_force_redirect_in'] = ['/catalogue', ''];

        $helper = $this->createHelper(['keycloak' => $config]);

        $this->assertNull($helper->resolveRedirectUrl($this->createNavigation('/catalogue/courses')));
        $this->assertSame(
            '/connect/keycloak',
            $helper->resolveRedirectUrl($this->createNavigation('/admin')),
            'An empty fragment must not exempt every path.'
        );
    }

    /**
     * These paths carry the handshake or end the session. Redirecting them loops forever.
     *
     * @dataProvider alwaysExemptPathProvider
     */
    public function testProviderHandshakeAndLogoutAreAlwaysExempt(string $path): void
    {
        $helper = $this->createHelper(['keycloak' => $this->providerConfig(true)]);

        $this->assertNull($helper->resolveRedirectUrl($this->createNavigation($path)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function alwaysExemptPathProvider(): array
    {
        return [
            'provider start' => ['/connect/keycloak'],
            'provider check' => ['/connect/keycloak/check'],
            'logout' => ['/logout'],
            'api' => ['/api/users'],
            'oauth server' => ['/oauth/authorize'],
            'scim' => ['/scim/v2/Users'],
            'mcp' => ['/mcp'],
        ];
    }

    /**
     * The escape hatch is what keeps an unreachable provider from locking everybody out.
     * It survives its own request, because the SPA then navigates without a page load.
     */
    public function testEscapeParameterReachesTheLoginFormAndSurvivesTheNextPageLoad(): void
    {
        $helper = $this->createHelper(['keycloak' => $this->providerConfig(true)]);
        $session = new Session(new MockArraySessionStorage());

        $escaping = $this->createNavigation('/login?skipForcedRedirect=1');
        $escaping->setSession($session);

        $this->assertNull($helper->resolveRedirectUrl($escaping));
        $this->assertTrue($session->get(ForcedLoginRedirectHelper::SESSION_KEY));

        $next = $this->createNavigation('/login');
        $next->setSession($session);

        $this->assertNull($helper->resolveRedirectUrl($next));
        $this->assertTrue($helper->isEscapeActive($next));
    }

    /**
     * The administrator hands the platform back without waiting for the session to expire.
     */
    public function testEscapeParameterCanBeCleared(): void
    {
        $helper = $this->createHelper(['keycloak' => $this->providerConfig(true)]);
        $session = new Session(new MockArraySessionStorage());
        $session->set(ForcedLoginRedirectHelper::SESSION_KEY, true);

        $clearing = $this->createNavigation('/login?skipForcedRedirect=0');
        $clearing->setSession($session);

        $this->assertSame('/connect/keycloak', $helper->resolveRedirectUrl($clearing));
        $this->assertFalse($session->has(ForcedLoginRedirectHelper::SESSION_KEY));
    }

    /**
     * The first enabled provider wins, so the answer never depends on file order later on.
     */
    public function testFirstEnabledProviderWins(): void
    {
        $helper = $this->createHelper([
            'generic' => $this->providerConfig(true),
            'keycloak' => $this->providerConfig(true),
        ]);

        $this->assertSame(
            '/connect/generic',
            $helper->resolveRedirectUrl($this->createNavigation('/admin'))
        );
    }

    /**
     * The declaration guard runs first, so a platform that never redirects touches
     * neither the session nor the access URL. The empty session proves that order.
     */
    public function testTheOptionBeingOffLeavesTheRequestUntouched(): void
    {
        $helper = $this->createHelper(['keycloak' => $this->providerConfig(false)]);
        $session = new Session(new MockArraySessionStorage());

        $request = $this->createNavigation('/login?skipForcedRedirect=1');
        $request->setSession($session);

        $this->assertNull($helper->resolveRedirectUrl($request));
        $this->assertFalse($session->has(ForcedLoginRedirectHelper::SESSION_KEY));
    }

    /**
     * A URL parameter must not undo force_as_login_method, which is an interface choice.
     */
    public function testEscapeParameterDoesNothingWhileTheOptionIsOff(): void
    {
        $helper = $this->createHelper(['keycloak' => $this->providerConfig(false)]);

        $this->assertFalse($helper->isEscapeActive($this->createNavigation('/login?skipForcedRedirect=1')));
    }

    /**
     * A provider name Chamilo does not implement must not turn every page into a 500.
     */
    public function testUnknownProviderLeavesThePlatformReachable(): void
    {
        $helper = $this->createHelper(['saml' => $this->providerConfig(true)]);

        $this->assertNull($helper->resolveRedirectUrl($this->createNavigation('/admin')));
    }

    private function createNavigation(string $uri): Request
    {
        $request = Request::create($uri, 'GET');
        $request->headers->set('Sec-Fetch-Dest', 'document');
        $request->headers->set('Accept', 'text/html,application/xhtml+xml');

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function providerConfig(bool $forceRedirect): array
    {
        return [
            'enabled' => true,
            'force_redirect' => $forceRedirect,
            'skip_force_redirect_in' => [],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $providers
     */
    private function createHelper(array $providers): ForcedLoginRedirectHelper
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(
                static function (string $name): string {
                    $provider = str_replace(['chamilo.oauth2_', '_start'], '', $name);

                    // Same answer the router gives for a provider Chamilo does not implement.
                    if (!\in_array($provider, ['generic', 'facebook', 'keycloak', 'azure'], true)) {
                        throw new RouteNotFoundException($name);
                    }

                    return '/connect/'.$provider;
                }
            )
        ;

        // AuthenticationConfigHelper is a readonly class PHPUnit 9 cannot double, so it is
        // built for real. Under the CLI SAPI the repository double leaves the access URL
        // empty, and the configuration resolves through "default".
        $configHelper = new AuthenticationConfigHelper(
            new ParameterBag(['authentication' => ['default' => ['oauth2' => $providers]]]),
            new AccessUrlHelper($this->createMock(AccessUrlRepository::class), new RequestStack())
        );

        return new ForcedLoginRedirectHelper($configHelper, $urlGenerator, new NullLogger());
    }
}
