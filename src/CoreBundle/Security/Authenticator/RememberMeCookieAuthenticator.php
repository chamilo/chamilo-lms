<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authenticator;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\ValidationToken;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ValidationTokenRepository;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class RememberMeCookieAuthenticator extends AbstractAuthenticator
{
    private const COOKIE_NAME = 'ch_remember_me';

    /**
     * 14 days.
     */
    private const TTL_SECONDS = 1209600;

    public function __construct(
        private readonly ValidationTokenRepository $tokenRepository,
        private readonly UserRepository $userRepository,
        private readonly TokenStorageInterface $tokenStorage,
        #[Autowire('%env(APP_SECRET)%')]
        private readonly string $appSecret,
    ) {}

    public function supports(Request $request): ?bool
    {
        $path = $request->getPathInfo();
        if ($this->shouldSkipPath($path)) {
            return false;
        }

        // If already authenticated as a real user, do nothing.
        $currentToken = $this->tokenStorage->getToken();
        if ($currentToken && $currentToken->getUser() instanceof User) {
            return false;
        }

        $cookieValue = $request->cookies->get(self::COOKIE_NAME);

        return \is_string($cookieValue) && '' !== $cookieValue;
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $cookieValue = (string) $request->cookies->get(self::COOKIE_NAME);

        $parsed = $this->parseCookieValue($cookieValue);
        if (null === $parsed) {
            $request->attributes->set('_remember_me_clear', true);

            throw new AuthenticationException('Invalid remember-me cookie format/signature.');
        }

        $userId = (int) $parsed['userId'];
        $rawToken = (string) $parsed['token'];
        $hash = hash('sha256', $rawToken);

        // Opportunistic cleanup.
        $cutoff = (new DateTimeImmutable())->modify('-'.self::TTL_SECONDS.' seconds');
        $this->tokenRepository->deleteExpiredRememberMeTokens($cutoff);

        $tokenEntity = $this->tokenRepository->findRememberMeToken($userId, $hash);
        if (!$tokenEntity instanceof ValidationToken) {
            $request->attributes->set('_remember_me_clear', true);

            throw new AuthenticationException('Remember-me token not found.');
        }

        if ($this->isExpired($tokenEntity)) {
            $this->tokenRepository->remove($tokenEntity, true);
            $request->attributes->set('_remember_me_clear', true);

            throw new AuthenticationException('Remember-me token expired.');
        }

        $user = $this->userRepository->find($userId);
        if (!$user instanceof User) {
            $this->tokenRepository->remove($tokenEntity, true);
            $request->attributes->set('_remember_me_clear', true);

            throw new AuthenticationException('User not found for remember-me token.');
        }

        // Safety checks.
        if (User::ACTIVE !== $user->getActive()) {
            $this->tokenRepository->remove($tokenEntity, true);
            $request->attributes->set('_remember_me_clear', true);

            throw new AuthenticationException('User not active.');
        }

        if (null !== $user->getExpirationDate() && $user->getExpirationDate() <= new DateTime()) {
            $this->tokenRepository->remove($tokenEntity, true);
            $request->attributes->set('_remember_me_clear', true);

            throw new AuthenticationException('User expired.');
        }

        // Prepare rotation (subscriber will commit rotation + set cookie on response).
        $newRaw = $this->generateRawToken();
        $newHash = hash('sha256', $newRaw);
        $newCookieValue = $this->buildCookieValue($userId, $newRaw);

        $request->attributes->set('_remember_me_rotate', [
            'oldId' => $tokenEntity->getId(),
            'userId' => $userId,
            'newHash' => $newHash,
            'newCookie' => $newCookieValue,
        ]);

        return new SelfValidatingPassport(
            new UserBadge((string) $userId, static fn () => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->attributes->set('_remember_me_clear', true);

        return null;
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
}
