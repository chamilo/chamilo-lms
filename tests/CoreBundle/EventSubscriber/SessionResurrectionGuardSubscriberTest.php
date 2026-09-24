<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\EventSubscriber\SessionResurrectionGuardSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * A request that released its session lock early can outlive a concurrent /logout. When
 * it then touches the session, PHP's strict mode hands it a new session id, and the
 * firewall would write the still-loaded token into it: the response's Set-Cookie logs the
 * browser back in (seen in CI: SpecialCase1 "I am logged as" landing on an admin page).
 * The guard's whole contract is below: a session id that changed without a login drops
 * the token; an unchanged id, or a login that migrated the session on purpose, keeps it.
 */
final class SessionResurrectionGuardSubscriberTest extends TestCase
{
    public function testSessionReplacedAfterAConcurrentLogoutDropsTheToken(): void
    {
        $tokenStorage = $this->authenticatedTokenStorage();

        $this->respond($tokenStorage, 'id-destroyed-by-logout', $this->startedSession('id-issued-by-php'));

        self::assertNull($tokenStorage->getToken(), 'The logged-out user must not be written into the new session.');
    }

    public function testUnchangedSessionKeepsTheToken(): void
    {
        $tokenStorage = $this->authenticatedTokenStorage();

        $this->respond($tokenStorage, 'same-id', $this->startedSession('same-id'));

        self::assertNotNull($tokenStorage->getToken(), 'An ordinary request must not log its user out.');
    }

    public function testReleasedButStillValidSessionIsRestartedAndKeepsTheToken(): void
    {
        $tokenStorage = $this->authenticatedTokenStorage();
        $session = $this->startedSession('same-id');
        // What ApiSessionUnlockSubscriber and /platform-config/list do mid-request.
        $session->save();

        $this->respond($tokenStorage, 'same-id', $session);

        self::assertTrue($session->isStarted());
        self::assertNotNull($tokenStorage->getToken(), 'Releasing the session lock early must not log the user out.');
    }

    public function testLoginMigratingTheSessionKeepsTheToken(): void
    {
        $tokenStorage = $this->authenticatedTokenStorage();
        $request = $this->requestWithSession('anonymous-id', $this->startedSession('id-after-login-migration'));
        $subscriber = new SessionResurrectionGuardSubscriber($tokenStorage);

        $subscriber->onLoginSuccess(new LoginSuccessEvent(
            $this->createMock(AuthenticatorInterface::class),
            $this->createMock(Passport::class),
            $this->createMock(TokenInterface::class),
            $request,
            null,
            'main',
        ));
        $subscriber->onKernelResponse($this->responseEvent($request));

        self::assertNotNull($tokenStorage->getToken(), 'A login rotates the session id on purpose.');
    }

    private function respond(TokenStorage $tokenStorage, string $cookieSessionId, Session $session): void
    {
        (new SessionResurrectionGuardSubscriber($tokenStorage))
            ->onKernelResponse($this->responseEvent($this->requestWithSession($cookieSessionId, $session)))
        ;
    }

    private function authenticatedTokenStorage(): TokenStorage
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($this->createMock(TokenInterface::class));

        return $tokenStorage;
    }

    private function startedSession(string $id): Session
    {
        $storage = new MockArraySessionStorage();
        $storage->setId($id);
        $session = new Session($storage);
        $session->start();

        return $session;
    }

    private function requestWithSession(string $cookieSessionId, Session $session): Request
    {
        $request = new Request(cookies: [$session->getName() => $cookieSessionId]);
        $request->setSession($session);

        return $request;
    }

    private function responseEvent(Request $request): ResponseEvent
    {
        return new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );
    }
}
