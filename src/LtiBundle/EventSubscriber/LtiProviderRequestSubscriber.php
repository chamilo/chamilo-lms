<?php

declare(strict_types=1);

namespace Chamilo\LtiBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\LtiBundle\Security\LtiProviderLaunchToken;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class LtiProviderRequestSubscriber implements EventSubscriberInterface
{
    private const TOKEN_PARAM = 'lti_provider_token';
    private const LAUNCH_ID_PARAM = 'lti_launch_id';
    private const SESSION_CONTEXT_KEY = '_ltiProvider';
    private const MAIN_PREFIX = '/main/';

    /**
     * Exclude profiler and static directories. We only want legacy PHP entrypoints.
     */
    private const EXCLUDED_PREFIXES = [
        '/_wdt',
        '/_profiler',
        '/build/',
        '/bundles/',
        '/media/',
        '/favicon.ico',
        '/main/build/',
        '/main/css/',
        '/main/img/',
        '/main/image/',
        '/main/images/',
        '/main/font/',
        '/main/fonts/',
        '/main/js/',
        '/main/assets/',
        '/main/node_modules/',
    ];

    public function __construct(
        private readonly LtiProviderLaunchToken $launchToken,
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 2048],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $this->getLegacyRequestPath($request);

        if (!$this->supportsRequest($path)) {
            return;
        }

        $this->logger->debug('[LtiProvider subscriber] Request info.', [
            'path' => $path,
            'path_info' => $request->getPathInfo(),
            'request_uri' => $request->server->get('REQUEST_URI'),
            'method' => $request->getMethod(),
            'has_query_token' => $request->query->has(self::TOKEN_PARAM),
            'has_post_token' => $request->request->has(self::TOKEN_PARAM),
            'has_cookie_token' => $request->cookies->has(self::TOKEN_PARAM),
            'has_session' => $request->hasSession(),
        ]);

        $context = $this->resolveLaunchContext($request);

        if ([] === $context) {
            return;
        }

        $userId = (int) ($context['user_id'] ?? 0);
        $courseId = (int) ($context['course_id'] ?? 0);
        $courseCode = (string) ($context['course_code'] ?? '');
        $sessionId = (int) ($context['session_id'] ?? 0);
        $token = (string) ($context['token'] ?? '');
        $launchId = (string) ($context['launch_id'] ?? '');

        if ($userId <= 0 || ($courseId <= 0 && '' === $courseCode) || '' === $token) {
            $this->logger->warning('[LtiProvider subscriber] Missing required launch context.', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'course_code' => $courseCode,
                'launch_id' => $launchId,
            ]);

            return;
        }

        /** @var User|null $user */
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user instanceof User) {
            $this->logger->warning('[LtiProvider subscriber] User could not be resolved.', [
                'user_id' => $userId,
                'launch_id' => $launchId,
            ]);

            return;
        }

        /** @var Course|null $course */
        $course = null;

        if ($courseId > 0) {
            $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        }

        if (!$course instanceof Course && '' !== $courseCode) {
            $course = $this->entityManager->getRepository(Course::class)->findOneBy([
                'code' => $courseCode,
            ]);
        }

        if (!$course instanceof Course) {
            $this->logger->warning('[LtiProvider subscriber] Course could not be resolved.', [
                'course_id' => $courseId,
                'course_code' => $courseCode,
                'launch_id' => $launchId,
            ]);

            return;
        }

        if (!$this->isUserSubscribedToCourse($user, $course)) {
            $this->logger->warning('[LtiProvider subscriber] User is not subscribed to the target course.', [
                'user_id' => $user->getId(),
                'course_id' => $course->getId(),
                'course_code' => $course->getCode(),
                'launch_id' => $launchId,
            ]);

            return;
        }

        if (!$request->hasSession()) {
            $this->logger->warning('[LtiProvider subscriber] Request has no session. LTI context cannot be restored.', [
                'path' => $path,
                'launch_id' => $launchId,
            ]);

            return;
        }

        $session = $request->getSession();

        $normalizedContext = [
            'token' => $token,
            'launch_id' => $launchId,
            'user_id' => $user->getId(),
            'course_id' => $course->getId(),
            'course_code' => $course->getCode(),
            'session_id' => $sessionId,
            'restored_at' => time(),
            'source' => (string) ($context['source'] ?? 'unknown'),
        ];

        $this->enrichRequest($request, $normalizedContext);
        $this->restoreLegacySessionContext($session, $user, $course, $normalizedContext);
        $this->restoreSecurityContext($session, $user);

        $request->attributes->set('_lti_provider_access_restored', true);
        $request->attributes->set('_lti_provider_user_id', $user->getId());
        $request->attributes->set('_lti_provider_course_id', $course->getId());
        $request->attributes->set('_lti_provider_course_code', $course->getCode());
        $request->attributes->set('_lti_provider_session_id', $sessionId);
    }

    private function resolveLaunchContext(Request $request): array
    {
        $token = $this->resolveTokenFromRequest($request);
        $launchId = $this->resolveLaunchIdFromRequest($request);

        if (null !== $token && '' !== $token) {
            try {
                $payload = $this->launchToken->parseToken($token);

                if (\is_array($payload)) {
                    return $this->normalizeContext($payload, $token, $launchId, 'token');
                }
            } catch (\Throwable $throwable) {
                $this->logger->warning('[LtiProvider subscriber] Failed to parse launch token.', [
                    'message' => $throwable->getMessage(),
                ]);
            }
        }

        if (!$this->canUseStoredContextFallback($request)) {
            return [];
        }

        if ($request->hasSession()) {
            $storedContext = $request->getSession()->get(self::SESSION_CONTEXT_KEY);

            if (\is_array($storedContext) && !empty($storedContext)) {
                return $this->normalizeContext(
                    $storedContext,
                    (string) ($storedContext['token'] ?? ''),
                    (string) ($storedContext['launch_id'] ?? $launchId),
                    'session'
                );
            }
        }

        return [];
    }

    private function resolveTokenFromRequest(Request $request): ?string
    {
        $candidates = [
            $request->query->get(self::TOKEN_PARAM),
            $request->request->get(self::TOKEN_PARAM),
            $request->headers->get('X-Lti-Provider-Token'),
        ];

        if ($this->canUseStoredContextFallback($request)) {
            $candidates[] = $request->cookies->get(self::TOKEN_PARAM);

            if ($request->hasSession()) {
                $storedContext = $request->getSession()->get(self::SESSION_CONTEXT_KEY);

                if (\is_array($storedContext)) {
                    $candidates[] = $storedContext['token'] ?? null;
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (\is_string($candidate) && '' !== trim($candidate)) {
                return trim($candidate);
            }
        }

        return null;
    }

    private function resolveLaunchIdFromRequest(Request $request): ?string
    {
        $candidates = [
            $request->query->get(self::LAUNCH_ID_PARAM),
            $request->request->get(self::LAUNCH_ID_PARAM),
            $request->headers->get('X-Lti-Launch-Id'),
        ];

        if ($this->canUseStoredContextFallback($request)) {
            $candidates[] = $request->cookies->get(self::LAUNCH_ID_PARAM);

            if ($request->hasSession()) {
                $storedContext = $request->getSession()->get(self::SESSION_CONTEXT_KEY);

                if (\is_array($storedContext)) {
                    $candidates[] = $storedContext['launch_id'] ?? null;
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (\is_string($candidate) && '' !== trim($candidate)) {
                return trim($candidate);
            }
        }

        return null;
    }

    private function canUseStoredContextFallback(Request $request): bool
    {
        if ($request->query->has(self::TOKEN_PARAM) || $request->request->has(self::TOKEN_PARAM)) {
            return true;
        }

        if ($request->isXmlHttpRequest()) {
            return true;
        }

        $referer = (string) $request->headers->get('referer', '');

        if ('' !== $referer) {
            $refererPath = (string) (parse_url($referer, \PHP_URL_PATH) ?: '');

            if (str_starts_with($refererPath, self::MAIN_PREFIX)) {
                return true;
            }
        }

        $secFetchDest = strtolower((string) $request->headers->get('sec-fetch-dest', ''));

        if (\in_array($secFetchDest, ['iframe', 'document', 'empty', 'image', 'script', 'style', 'font'], true)) {
            return true;
        }

        return false;
    }

    private function normalizeContext(array $payload, string $token, ?string $launchId, string $source): array
    {
        $userId = (int) ($payload['user_id'] ?? $payload['uid'] ?? $payload['user'] ?? 0);
        $courseId = (int) ($payload['course_id'] ?? $payload['c_id'] ?? $payload['cid'] ?? $payload['real_cid'] ?? 0);
        $courseCode = (string) ($payload['course_code'] ?? $payload['cidReq'] ?? $payload['course'] ?? $payload['code'] ?? '');
        $sessionId = (int) ($payload['session_id'] ?? $payload['sid'] ?? 0);

        return [
            'token' => '' !== $token ? $token : (string) ($payload['token'] ?? ''),
            'launch_id' => (string) ($launchId ?? $payload['launch_id'] ?? $payload['lti_launch_id'] ?? ''),
            'user_id' => $userId,
            'course_id' => $courseId,
            'course_code' => $courseCode,
            'session_id' => $sessionId,
            'source' => $source,
        ];
    }

    private function enrichRequest(Request $request, array $context): void
    {
        $courseCode = (string) ($context['course_code'] ?? '');
        $courseId = (int) ($context['course_id'] ?? 0);
        $sessionId = (int) ($context['session_id'] ?? 0);
        $token = (string) ($context['token'] ?? '');
        $launchId = (string) ($context['launch_id'] ?? '');

        if ('' !== $courseCode && !$request->query->has('cidReq')) {
            $request->query->set('cidReq', $courseCode);
        }

        if ($courseId > 0 && !$request->query->has('c_id')) {
            $request->query->set('c_id', (string) $courseId);
        }

        if ($sessionId > 0 && !$request->query->has('sid')) {
            $request->query->set('sid', (string) $sessionId);
        }

        if ('' !== $token && !$request->query->has(self::TOKEN_PARAM)) {
            $request->query->set(self::TOKEN_PARAM, $token);
        }

        if ('' !== $launchId && !$request->query->has(self::LAUNCH_ID_PARAM)) {
            $request->query->set(self::LAUNCH_ID_PARAM, $launchId);
        }

        $request->attributes->set('cidReq', $courseCode);
        $request->attributes->set('c_id', $courseId);

        if ($sessionId > 0) {
            $request->attributes->set('sid', $sessionId);
        }
    }

    private function restoreLegacySessionContext(
        SessionInterface $session,
        User $user,
        Course $course,
        array $context
    ): void {
        $legacyUser = $this->buildLegacyUserContext($user);
        $legacyCourse = $this->buildLegacyCourseContext($course);

        $this->setSessionValue($session, '_uid', $user->getId());
        $this->setSessionValue($session, '_user', $legacyUser);
        $this->setSessionValue($session, '_cid', $course->getCode());
        $this->setSessionValue($session, '_real_cid', $course->getId());
        $this->setSessionValue($session, '_course', $legacyCourse);
        $this->setSessionValue($session, 'is_allowed_in_course', true);
        $this->setSessionValue($session, self::SESSION_CONTEXT_KEY, $context);
    }

    private function restoreSecurityContext(SessionInterface $session, User $user): void
    {
        $roles = $user->getRoles();
        $securityToken = new UsernamePasswordToken($user, 'main', $roles);

        $this->tokenStorage->setToken($securityToken);
        $this->setSessionValue($session, '_security_main', serialize($securityToken));
    }

    private function buildLegacyUserContext(User $user): array
    {
        return [
            'id' => $user->getId(),
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
        ];
    }

    private function buildLegacyCourseContext(Course $course): array
    {
        return [
            'id' => $course->getId(),
            'real_id' => $course->getId(),
            'code' => $course->getCode(),
        ];
    }

    private function setSessionValue(SessionInterface $session, string $key, mixed $value): void
    {
        $session->set($key, $value);
        $_SESSION[$key] = $value;
    }

    private function isUserSubscribedToCourse(User $user, Course $course): bool
    {
        try {
            $result = \CourseManager::is_user_subscribed_in_course($user->getId(), $course->getCode());

            if (!$result) {
                $result = \CourseManager::is_user_subscribed_in_course($course->getCode(), $user->getId());
            }

            return (bool) $result;
        } catch (\Throwable $throwable) {
            $this->logger->warning('[LtiProvider subscriber] Subscription check failed. Falling back to trusting launch validation.', [
                'user_id' => $user->getId(),
                'course_code' => $course->getCode(),
                'message' => $throwable->getMessage(),
            ]);

            return true;
        }
    }

    private function supportsRequest(string $path): bool
    {
        if ('' === $path || '/' === $path) {
            return false;
        }

        foreach (self::EXCLUDED_PREFIXES as $excludedPrefix) {
            if (str_starts_with($path, $excludedPrefix)) {
                return false;
            }
        }

        if (!str_starts_with($path, self::MAIN_PREFIX)) {
            return false;
        }

        return 1 === preg_match('~\.php(?:/.*)?$~i', $path);
    }

    private function getLegacyRequestPath(Request $request): string
    {
        $path = $request->getPathInfo();

        if ('' === $path || '/' === $path) {
            $requestUri = (string) $request->server->get('REQUEST_URI', '');
            $path = (string) (parse_url($requestUri, \PHP_URL_PATH) ?: '/');
        }

        $this->logger->debug('[LtiProvider subscriber] Resolved legacy request path.', [
            'resolved_path' => $path,
            'path_info' => $request->getPathInfo(),
            'request_uri' => $request->server->get('REQUEST_URI'),
        ]);

        return $path;
    }
}
