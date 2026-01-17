<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\ValidationToken;
use Chamilo\CoreBundle\Helpers\ValidationTokenHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ValidationTokenRepository;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

final class RememberMeSubscriber implements EventSubscriberInterface
{
    private const COOKIE_NAME = 'ch_remember_me';

    /**
     * Extended session lifetime (seconds).
     * 14 days = 14 * 24 * 60 * 60 = 1209600.
     */
    private const TTL_SECONDS = 1209600;

    // Toggle to debug quickly via PHP error log.
    private const DEBUG = false;

    public function __construct(
        private readonly ValidationTokenRepository $tokenRepository,
        private readonly UserRepository $userRepository,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly SessionAuthenticationStrategyInterface $sessionStrategy,
        private readonly string $appSecret,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Run AFTER the firewall ContextListener loads the session token.
            KernelEvents::REQUEST => ['onKernelRequest', -10],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
            LoginSuccessEvent::class => ['onLoginSuccess', 0],
            LogoutEvent::class => ['onLogout', 0],
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        $token = $event->getAuthenticatedToken();
        $user = $token?->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (!$this->isRememberMeRequested($request)) {
            return;
        }

        $secure = $this->isRequestSecure($request);

        $userId = (int) $user->getId();
        $raw = $this->generateRawToken();
        $hash = hash('sha256', $raw);
        $cookieValue = $this->buildCookieValue($userId, $raw);

        // Keep it simple: one active token per user.
        $this->tokenRepository->deleteRememberMeTokensForUser($userId);

        $tokenEntity = new ValidationToken(ValidationTokenHelper::TYPE_REMEMBER_ME, $userId, $hash);
        $this->tokenRepository->save($tokenEntity, true);

        $expiresAt = (new DateTimeImmutable())->modify('+'.self::TTL_SECONDS.' seconds');
        $event->getResponse()->headers->setCookie($this->makeRememberMeCookie($cookieValue, $expiresAt, $secure));

        $this->debug('Issued remember-me cookie on login success', [
            'userId' => $userId,
            'secure' => $secure,
        ]);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $path = $request->getPathInfo();
        if ($this->shouldSkipPath($path)) {
            return;
        }

        $currentToken = $this->tokenStorage->getToken();
        if ($currentToken && $currentToken->getUser() instanceof User) {
            return; // Already authenticated as a real user
        }

        $cookieValue = $request->cookies->get(self::COOKIE_NAME);
        if (!\is_string($cookieValue) || '' === $cookieValue) {
            $this->debug('No remember-me cookie on request');

            return;
        }

        $parsed = $this->parseCookieValue($cookieValue);
        if (null === $parsed) {
            $request->attributes->set('_remember_me_clear', true);
            $this->debug('Invalid remember-me cookie format/signature');

            return;
        }

        $userId = $parsed['userId'];
        $rawToken = $parsed['token'];
        $hash = hash('sha256', $rawToken);

        // Opportunistic cleanup.
        $cutoff = (new DateTimeImmutable())->modify('-'.self::TTL_SECONDS.' seconds');
        $this->tokenRepository->deleteExpiredRememberMeTokens($cutoff);

        $tokenEntity = $this->tokenRepository->findRememberMeToken($userId, $hash);
        if (!$tokenEntity) {
            $request->attributes->set('_remember_me_clear', true);
            $this->debug('Remember-me token not found in DB', ['userId' => $userId]);

            return;
        }

        if ($this->isExpired($tokenEntity)) {
            $this->tokenRepository->remove($tokenEntity, true);
            $request->attributes->set('_remember_me_clear', true);
            $this->debug('Remember-me token expired', ['userId' => $userId]);

            return;
        }

        $user = $this->userRepository->find($userId);
        if (!$user instanceof User) {
            $this->tokenRepository->remove($tokenEntity, true);
            $request->attributes->set('_remember_me_clear', true);
            $this->debug('User not found for remember-me token', ['userId' => $userId]);

            return;
        }

        // Basic safety checks.
        if (User::ACTIVE !== $user->getActive()) {
            $this->tokenRepository->remove($tokenEntity, true);
            $request->attributes->set('_remember_me_clear', true);
            $this->debug('User not active for remember-me token', ['userId' => $userId]);

            return;
        }

        if (null !== $user->getExpirationDate() && $user->getExpirationDate() <= new DateTime()) {
            $this->tokenRepository->remove($tokenEntity, true);
            $request->attributes->set('_remember_me_clear', true);
            $this->debug('User expired for remember-me token', ['userId' => $userId]);

            return;
        }

        // Authenticate user.
        $firewallName = $this->guessFirewallName($request);
        $securityToken = new UsernamePasswordToken($user, $firewallName, $user->getRoles());
        $this->tokenStorage->setToken($securityToken);

        // Prevent session fixation.
        if ($request->hasSession()) {
            $request->getSession()->start();
        }
        $this->sessionStrategy->onAuthentication($request, $securityToken);

        // Rotate token (commit on RESPONSE).
        $newRaw = $this->generateRawToken();
        $newHash = hash('sha256', $newRaw);
        $newCookieValue = $this->buildCookieValue($userId, $newRaw);

        $request->attributes->set('_remember_me_rotate', [
            'oldId' => $tokenEntity->getId(),
            'userId' => $userId,
            'newHash' => $newHash,
            'newCookie' => $newCookieValue,
        ]);

        $this->debug('Remember-me authentication succeeded', [
            'userId' => $userId,
            'firewall' => $firewallName,
        ]);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $secure = $this->isRequestSecure($request);

        if ($request->attributes->getBoolean('_remember_me_clear')) {
            // Clear both variants to avoid issues when switching HTTP/HTTPS in dev.
            $response->headers->setCookie($this->makeExpiredCookie(true));
            $response->headers->setCookie($this->makeExpiredCookie(false));

            return;
        }

        $rotate = $request->attributes->get('_remember_me_rotate');
        if (!\is_array($rotate)) {
            return;
        }

        $oldId = (int) ($rotate['oldId'] ?? 0);
        $userId = (int) ($rotate['userId'] ?? 0);
        $newHash = (string) ($rotate['newHash'] ?? '');
        $newCookie = (string) ($rotate['newCookie'] ?? '');

        if ($oldId <= 0 || $userId <= 0 || '' === $newHash || '' === $newCookie) {
            return;
        }

        // Store new token first, then delete old token.
        $newTokenEntity = new ValidationToken(ValidationTokenHelper::TYPE_REMEMBER_ME, $userId, $newHash);
        $this->tokenRepository->save($newTokenEntity, true);

        $this->tokenRepository->deleteRememberMeTokenById($oldId);

        $expiresAt = (new DateTimeImmutable())->modify('+'.self::TTL_SECONDS.' seconds');
        $response->headers->setCookie($this->makeRememberMeCookie($newCookie, $expiresAt, $secure));
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if ($user instanceof User) {
            $this->tokenRepository->deleteRememberMeTokensForUser((int) $user->getId());
        }

        $secure = $this->isRequestSecure($event->getRequest());
        $event->getResponse()?->headers->setCookie($this->makeExpiredCookie($secure));
    }

    private function isRememberMeRequested(Request $request): bool
    {
        // Try common checkbox names (adapt if your form uses a different name).
        $value = $request->request->get('_remember_me')
            ?? $request->request->get('remember_me')
            ?? $request->request->get('rememberme')
            ?? $request->request->get('ch_remember_me');

        if (null === $value) {
            return false;
        }

        return \in_array((string) $value, ['1', 'on', 'yes', 'true'], true);
    }

    private function shouldSkipPath(string $path): bool
    {
        return str_starts_with($path, '/login')
            || str_starts_with($path, '/logout')
            || str_starts_with($path, '/validate')
            || str_starts_with($path, '/_wdt')
            || str_starts_with($path, '/_profiler');
    }

    private function isExpired(ValidationToken $token): bool
    {
        $createdTs = $token->getCreatedAt()->getTimestamp();

        return ($createdTs + self::TTL_SECONDS) < time();
    }

    private function guessFirewallName(Request $request): string
    {
        $context = $request->attributes->get('_firewall_context');
        if (\is_string($context) && preg_match('/\.([^.]+)$/', $context, $m)) {
            return (string) $m[1];
        }

        return 'main';
    }

    private function parseCookieValue(string $value): ?array
    {
        $parts = explode(':', $value);
        if (3 !== \count($parts)) {
            return null;
        }

        [$userIdRaw, $token, $sig] = $parts;

        if (!ctype_digit($userIdRaw)) {
            return null;
        }

        $userId = (int) $userIdRaw;
        if ($userId <= 0 || '' === $token || '' === $sig) {
            return null;
        }

        $expected = $this->sign($userIdRaw.'|'.$token);
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        return ['userId' => $userId, 'token' => $token];
    }

    private function buildCookieValue(int $userId, string $rawToken): string
    {
        $sig = $this->sign((string) $userId.'|'.$rawToken);

        return $userId.':'.$rawToken.':'.$sig;
    }

    private function sign(string $data): string
    {
        $raw = hash_hmac('sha256', $data, $this->appSecret, true);

        return $this->base64UrlEncode($raw);
    }

    private function generateRawToken(): string
    {
        return $this->base64UrlEncode(random_bytes(32));
    }

    private function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function makeRememberMeCookie(string $value, DateTimeImmutable $expiresAt, bool $secure): Cookie
    {
        return new Cookie(
            self::COOKIE_NAME,
            $value,
            $expiresAt->getTimestamp(),
            '/',
            null,
            $secure,
            true,
            false,
            Cookie::SAMESITE_LAX
        );
    }

    private function makeExpiredCookie(bool $secure): Cookie
    {
        return new Cookie(
            self::COOKIE_NAME,
            '',
            time() - 3600,
            '/',
            null,
            $secure,
            true,
            false,
            Cookie::SAMESITE_LAX
        );
    }

    private function isRequestSecure(Request $request): bool
    {
        if ($request->isSecure()) {
            return true;
        }

        // Helps when behind a reverse proxy and trusted proxies aren't configured.
        $xfp = strtolower((string) $request->headers->get('x-forwarded-proto', ''));

        return str_contains($xfp, 'https');
    }

    private function debug(string $message, array $context = []): void
    {
        if (!self::DEBUG) {
            return;
        }

        error_log('[remember-me] '.$message.' '.json_encode($context));
    }
}
