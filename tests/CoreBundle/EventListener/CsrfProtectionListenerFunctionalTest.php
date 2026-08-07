<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\EventListener;

use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks that CsrfProtectionListener is actually wired into the kernel, which
 * the unit test cannot prove: that it runs on a real API Platform route, and
 * that it runs *before* the firewall — a forged request must be rejected as a
 * CSRF failure (403), not as an authentication failure (401).
 *
 * Requests are sent unauthenticated on purpose. The listener only needs the
 * session cookie to consider a request a CSRF candidate, so the response code
 * alone tells which of the two gates stopped it.
 */
final class CsrfProtectionListenerFunctionalTest extends WebTestCase
{
    private const string API_ROUTE = '/api/terms_and_conditions_translation';

    private const string ENFORCE_VAR = 'CHAMILO_CSRF_ENFORCE';

    /** Message CsrfProtectionListener rejects with, used to tell its 403 apart
     * from an authorization one on the same endpoint. */
    private const string CSRF_REJECTION_MESSAGE = 'The security token is invalid.';

    private ?string $previousEnv = null;

    private ?string $previousServer = null;

    protected function setUp(): void
    {
        // The listener only rejects when enforcing; the shipped default is
        // log-only. The kernel must be down so the parameter is re-resolved.
        self::ensureKernelShutdown();

        $this->previousEnv = $_ENV[self::ENFORCE_VAR] ?? null;
        $this->previousServer = $_SERVER[self::ENFORCE_VAR] ?? null;

        // Both superglobals, in this order of importance: EnvVarProcessor reads
        // $_ENV first and only falls back to $_SERVER. Setting $_SERVER alone
        // works on a machine whose .env does not define the variable, and stops
        // working wherever it does -- which is exactly what CI does, since
        // .env.dist ships it set to false.
        $_ENV[self::ENFORCE_VAR] = $_SERVER[self::ENFORCE_VAR] = '1';
    }

    protected function tearDown(): void
    {
        // Put both back the way they were, so a value coming from .env is not
        // wiped for whatever runs next in the same process.
        if (null === $this->previousEnv) {
            unset($_ENV[self::ENFORCE_VAR]);
        } else {
            $_ENV[self::ENFORCE_VAR] = $this->previousEnv;
        }

        if (null === $this->previousServer) {
            unset($_SERVER[self::ENFORCE_VAR]);
        } else {
            $_SERVER[self::ENFORCE_VAR] = $this->previousServer;
        }

        parent::tearDown();
    }

    /**
     * Sanity check: if enforcement does not actually reach the listener, every
     * rejection test below would fail with a confusing "401 is not 403" rather
     * than pointing at the configuration.
     */
    public function testEnforcementReachesTheListener(): void
    {
        self::createClient();

        self::assertTrue(self::getContainer()->getParameter('chamilo.csrf.enforce'));
    }

    public function testForgedRequestIsRejectedBeforeAuthentication(): void
    {
        $client = $this->clientWithSessionCookie();

        $this->post($client, []);

        // 403 rather than 401 proves the listener runs ahead of the firewall.
        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    public function testForeignOriginIsRejected(): void
    {
        $client = $this->clientWithSessionCookie();

        $this->post($client, ['HTTP_ORIGIN' => 'https://evil.example']);

        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    public function testSameOriginRequestReachesTheFirewall(): void
    {
        $client = $this->clientWithSessionCookie();

        $this->post($client, ['HTTP_SEC_FETCH_SITE' => 'same-origin']);

        // Anything but 403 means CSRF let it through; unauthenticated, the
        // firewall is what answers.
        self::assertNotSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    public function testBearerAuthenticatedRequestIsNotValidated(): void
    {
        $client = $this->clientWithSessionCookie();

        $this->post($client, ['HTTP_AUTHORIZATION' => 'Bearer not.a.real.token']);

        self::assertNotSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    public function testRequestWithoutSessionCookieIsNotValidated(): void
    {
        $client = self::createClient();

        $this->post($client, []);

        self::assertNotSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    public function testSafeMethodIsNotValidated(): void
    {
        $client = $this->clientWithSessionCookie();

        $client->request('GET', self::API_ROUTE);

        self::assertNotSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    /**
     * The two cases below use a real logged-in admin, so they exercise what the
     * SPA actually does rather than just the status code of an anonymous call.
     * Same user, same endpoint, same payload — only the origin differs.
     */
    public function testAuthenticatedWriteFromTheSameOriginPassesTheCsrfGate(): void
    {
        $client = $this->authenticatedClient();

        $this->post($client, ['HTTP_ORIGIN' => 'http://localhost']);

        // Deliberately not asserting on the status code: whatever the endpoint
        // answers past this point (validation, authorization) is not this
        // listener's business. What matters is that the rejection is not ours.
        self::assertStringNotContainsString(
            self::CSRF_REJECTION_MESSAGE,
            (string) $client->getResponse()->getContent()
        );
    }

    public function testAuthenticatedWriteWithoutOriginIsRejectedAsCsrf(): void
    {
        $client = $this->authenticatedClient();

        $this->post($client, []);

        // Same user, same endpoint, same payload as the case above — only the
        // origin differs, so the message is what pins the CSRF gate as the one
        // that rejected it rather than authorization.
        self::assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
        self::assertStringContainsString(
            self::CSRF_REJECTION_MESSAGE,
            (string) $client->getResponse()->getContent()
        );
    }

    /**
     * Logs in the admin account shipped by the fixtures, which yields a real
     * session cookie instead of the fabricated one the other cases use.
     */
    private function authenticatedClient(): KernelBrowser
    {
        $client = self::createClient();
        $admin = self::getContainer()->get(UserRepository::class)->findOneBy(['username' => 'admin']);

        self::assertNotNull($admin, 'The admin fixture must exist; run doctrine:fixtures:load.');

        $client->loginUser($admin);

        return $client;
    }

    /**
     * Sends the write request the listener is meant to guard.
     *
     * The content type matters: API Platform negotiates the format in
     * AddFormatListener (priority 28), well before this listener (priority 9),
     * so a POST without one is answered with 415 and never reaches the check.
     *
     * @param array<string, string> $server
     */
    private function post(KernelBrowser $client, array $server): void
    {
        $client->request(
            'POST',
            self::API_ROUTE,
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'] + $server,
            '{}'
        );
    }

    /**
     * A session cookie is what turns a request into a CSRF candidate. Its name
     * is read from the configured session rather than hardcoded, so renaming
     * it in framework.yaml cannot make this test silently stop covering
     * anything.
     */
    private function clientWithSessionCookie(): KernelBrowser
    {
        $client = self::createClient();
        $sessionName = self::getContainer()->get('session.factory')->createSession()->getName();

        $client->getCookieJar()->set(new Cookie($sessionName, 'a-session-id'));

        return $client;
    }
}
