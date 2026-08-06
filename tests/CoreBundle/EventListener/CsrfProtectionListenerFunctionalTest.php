<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\EventListener;

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

    protected function setUp(): void
    {
        // The listener only rejects when enforcing; the shipped default is
        // log-only. The kernel must be down so the parameter is re-resolved.
        static::ensureKernelShutdown();
        $_SERVER['CHAMILO_CSRF_ENFORCE'] = '1';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['CHAMILO_CSRF_ENFORCE']);

        parent::tearDown();
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
        $client = static::createClient();

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
        $client = static::createClient();
        $sessionName = static::getContainer()->get('session.factory')->createSession()->getName();

        $client->getCookieJar()->set(new Cookie($sessionName, 'a-session-id'));

        return $client;
    }
}
