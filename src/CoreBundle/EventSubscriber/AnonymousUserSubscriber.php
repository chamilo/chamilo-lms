<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Auto-login as an anonymous User entity ONLY within a public course context.
 * Keeps the anonymous session alive while the user stays around the course
 * (including auxiliary/XHR calls that may not carry cid), and clears it
 * when navigating away (top-level document navigation).
 */
class AnonymousUserSubscriber implements EventSubscriberInterface
{
    private const FIREWALL_NAME       = 'main';
    private const MAX_ANONYMOUS_USERS = 5;

    // Session flags for the “active public course” context
    private const S_ACTIVE_CID        = '_active_public_cid';
    private const S_ACTIVE_PUBLIC     = '_active_public_flag';
    private const S_ACTIVE_EXPIRES_AT = '_active_public_expires_at';
    private const S_SECURITY_TOKEN    = '_security_'.self::FIREWALL_NAME;

    // TTL (in seconds) for the “public course anonymous session” window
    private const ACTIVE_TTL_SECONDS  = 600; // 10 minutes

    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settings,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [ KernelEvents::REQUEST => 'onKernelRequest' ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request     = $event->getRequest();
        $hasSession  = $request->hasSession();
        $currentUser = $this->security->getUser();

        // Are we currently in a public course scope?
        $cid = $this->extractCid($request);
        if ($cid > 0 && $this->isCoursePublic($cid)) {
            // Refresh the “active public course” context (TTL)
            if ($hasSession) {
                $this->rememberActivePublicCid($request, $cid);
            }

            // If there is a real user (non-anonymous), do nothing
            if ($currentUser instanceof User && $currentUser->getStatus() !== User::ANONYMOUS) {
                return;
            }

            // If it's already an anonymous entity user, do nothing
            if ($currentUser instanceof User && $currentUser->getStatus() === User::ANONYMOUS) {
                return;
            }

            // 1.a) Log in as anonymous entity User (works with voters/Doctrine)
            $this->loginAnonymousEntity($request);

            return;
        }

        // We are not in a public course (or there is no cid on this request)
        if (!$hasSession) {
            return;
        }

        $session   = $request->getSession();
        $activeCid = (int) ($session->get(self::S_ACTIVE_CID, 0));
        $isActive  = (bool) ($session->get(self::S_ACTIVE_PUBLIC, false));
        $expiresAt = (int) ($session->get(self::S_ACTIVE_EXPIRES_AT, 0));
        $now       = time();

        // If there is an active public course context and it has not expired,
        //      DO NOT clear on XHR/assets. Only clear on top-level document navigation.
        if ($activeCid > 0 && $isActive && $expiresAt > $now) {
            if ($this->isTopLevelNavigation($request)) {
                // Navigated away from a course → clear the anonymous context
                $this->clearAnon($request);
            }
            return;
        }

        // If there is no active context or it expired and the user is anonymous → clear only on navigation
        if ($currentUser instanceof User && $currentUser->getStatus() === User::ANONYMOUS) {
            if ($this->isTopLevelNavigation($request)) {
                $this->clearAnon($request);
            }
        }
    }

    /**
     * Extract the course id from:
     *  - Query ?cid=...
     *  - Path /course/{id}/...
     *  - Path /api/courses/{id}
     */
    private function extractCid(Request $request): int
    {
        $cid = $request->query->get('cid');
        if (is_numeric($cid) && (int) $cid > 0) {
            return (int) $cid;
        }

        $path = $request->getPathInfo();

        if (preg_match('#^/course/(\d+)(?:/|$)#', $path, $m)) {
            return (int) $m[1];
        }

        if (preg_match('#^/api/courses/(\d+)(?:/|$)#', $path, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    private function isCoursePublic(int $cid): bool
    {
        /** @var Course|null $course */
        $course = $this->em->getRepository(Course::class)->find($cid);
        return $course?->isPublic() ?? false;
    }

    /** Store the active public course context in session and renew TTL. */
    private function rememberActivePublicCid(Request $request, int $cid): void
    {
        $session = $request->getSession();
        $session->set(self::S_ACTIVE_CID, $cid);
        $session->set(self::S_ACTIVE_PUBLIC, true);
        $session->set(self::S_ACTIVE_EXPIRES_AT, time() + self::ACTIVE_TTL_SECONDS);
    }

    /** Log in as an anonymous entity User (create/reuse and set a UsernamePasswordToken). */
    private function loginAnonymousEntity(Request $request): void
    {
        $userIp = $request->getClientIp() ?: '127.0.0.1';
        $anonId = $this->getOrCreateAnonymousUserId($userIp);
        if (null === $anonId) {
            return;
        }

        // Register login if it doesn't exist yet
        $trackRepo = $this->em->getRepository(TrackELogin::class);
        if (!$trackRepo->findOneBy(['userIp' => $userIp, 'user' => $anonId])) {
            $trackLogin = (new TrackELogin())
                ->setUserIp($userIp)
                ->setLoginDate(new DateTime())
                ->setUser($this->em->getReference(User::class, $anonId));
            $this->em->persist($trackLogin);
            $this->em->flush();
        }

        // Set token
        $userRepo = $this->em->getRepository(User::class);
        $user     = $userRepo->find($anonId);
        if (!$user) {
            return;
        }

        if ($request->hasSession()) {
            $request->getSession()->set('_user', [
                'user_id'         => $user->getId(),
                'username'        => $user->getUsername(),
                'firstname'       => $user->getFirstname(),
                'lastname'        => $user->getLastname(),
                'firstName'       => $user->getFirstname(),
                'lastName'        => $user->getLastname(),
                'email'           => $user->getEmail(),
                'official_code'   => $user->getOfficialCode(),
                'picture_uri'     => $user->getPictureUri(),
                'status'          => $user->getStatus(),
                'active'          => $user->isActive(),
                'theme'           => $user->getTheme(),
                'language'        => $user->getLocale(),
                'created_at'      => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'expiration_date' => $user->getExpirationDate() ? $user->getExpirationDate()->format('Y-m-d H:i:s') : null,
                'last_login'      => $user->getLastLogin() ? $user->getLastLogin()->format('Y-m-d H:i:s') : null,
                'is_anonymous'    => true,
            ]);
        }

        $roles = $user->getRoles();
        $this->tokenStorage->setToken(new UsernamePasswordToken($user, self::FIREWALL_NAME, $roles));
    }

    /** Clear token and session flags when navigating away from the course (top-level document navigation). */
    private function clearAnon(Request $request): void
    {
        $this->tokenStorage->setToken(null);

        if ($request->hasSession()) {
            $session = $request->getSession();
            $session->remove('_user');
            $session->remove(self::S_SECURITY_TOKEN);
            $session->remove(self::S_ACTIVE_CID);
            $session->remove(self::S_ACTIVE_PUBLIC);
            $session->remove(self::S_ACTIVE_EXPIRES_AT);
        }
    }

    /**
     * Consider it “top-level document navigation” if:
     *  - It is NOT an XHR (no `X-Requested-With: XMLHttpRequest`)
     *  - and browser sends `Sec-Fetch-Mode: navigate` and `Sec-Fetch-Dest: document`
     *  - or the Accept header includes `text/html`
     */
    private function isTopLevelNavigation(Request $request): bool
    {
        if ($request->isXmlHttpRequest()) {
            return false;
        }

        $mode = (string) $request->headers->get('Sec-Fetch-Mode', '');
        $dest = (string) $request->headers->get('Sec-Fetch-Dest', '');
        if ($mode === 'navigate' && $dest === 'document') {
            return true;
        }

        $accept = (string) $request->headers->get('Accept', '');
        return str_contains($accept, 'text/html');
    }

    private function getOrCreateAnonymousUserId(string $userIp): ?int
    {
        $userRepo   = $this->em->getRepository(User::class);
        $trackRepo  = $this->em->getRepository(TrackELogin::class);
        $autoProv   = 'true' === $this->settings->getSetting('security.anonymous_autoprovisioning');

        if (!$autoProv) {
            $u = $userRepo->findOneBy(['status' => User::ANONYMOUS], ['createdAt' => 'ASC']);
            return $u ? $u->getId() : $this->createAnonymousUser()->getId();
        }

        $max  = (int) $this->settings->getSetting('admin.max_anonymous_users') ?: self::MAX_ANONYMOUS_USERS;
        $list = $userRepo->findBy(['status' => User::ANONYMOUS], ['createdAt' => 'ASC']);

        // Reuse by IP if there is a previous login record
        foreach ($list as $u) {
            if ($trackRepo->findOneBy(['userIp' => $userIp, 'user' => $u])) {
                return $u->getId();
            }
        }

        // Trim excess anonymous users
        while (\count($list) >= $max) {
            $oldest = array_shift($list);
            if ($oldest) {
                $this->em->remove($oldest);
                $this->em->flush();
            }
        }

        return $this->createAnonymousUser()->getId();
    }

    private function createAnonymousUser(): User
    {
        $uniqueId = uniqid('anon_');
        $email    = $uniqueId.'@localhost.local';

        if ('true' === $this->settings->getSetting('profile.login_is_email')) {
            $uniqueId = $email;
        }

        $anonymousUser = (new User())
            ->setSkipResourceNode(true)
            ->setLastname('Doe')
            ->setFirstname('Anonymous')
            ->setUsername('anon_'.$uniqueId)
            ->setStatus(User::ANONYMOUS)
            ->setPlainPassword('anon')
            ->setEmail($email)
            ->setOfficialCode('anonymous')
            ->setCreatorId(1)
            ->addRole('ROLE_ANONYMOUS');

        $this->em->persist($anonymousUser);
        $this->em->flush();

        return $anonymousUser;
    }
}
