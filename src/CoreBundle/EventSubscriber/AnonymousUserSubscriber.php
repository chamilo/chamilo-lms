<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class AnonymousUserSubscriber implements EventSubscriberInterface
{
    private const MAX_ANONYMOUS_USERS = 5;
    private Security $security;
    private EntityManagerInterface $entityManager;
    private SessionInterface $session;
    private SettingsManager $settingsManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager, SessionInterface $session, SettingsManager $settingsManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->settingsManager = $settingsManager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (null !== $this->security->getUser()) {
            return;
        }

        $request = $event->getRequest();
        $userIp = $request->getClientIp() ?: '127.0.0.1';

        $anonymousUserId = $this->getOrCreateAnonymousUserId($userIp);
        if (null !== $anonymousUserId) {
            $trackLoginRepository = $this->entityManager->getRepository(TrackELogin::class);

            // Check if a login record already exists for this user and IP
            $existingLogin = $trackLoginRepository->findOneBy(['userIp' => $userIp, 'user' => $anonymousUserId]);
            if (!$existingLogin) {
                // Record the access if it does not exist
                $trackLogin = new TrackELogin();
                $trackLogin->setUserIp($userIp)
                    ->setLoginDate(new DateTime())
                    ->setUser($this->entityManager->getReference(User::class, $anonymousUserId))
                ;

                $this->entityManager->persist($trackLogin);
                $this->entityManager->flush();
            }

            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->find($anonymousUserId);

            if ($user) {
                // Store user information in the session
                $userInfo = [
                    'user_id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'firstName' => $user->getFirstname(),
                    'lastName' => $user->getLastname(),
                    'email' => $user->getEmail(),
                    'official_code' => $user->getOfficialCode(),
                    'picture_uri' => $user->getPictureUri(),
                    'status' => $user->getStatus(),
                    'active' => $user->getActive(),
                    'auth_source' => $user->getAuthSource(),
                    'theme' => $user->getTheme(),
                    'language' => $user->getLocale(),
                    'registration_date' => $user->getRegistrationDate()->format('Y-m-d H:i:s'),
                    'expiration_date' => $user->getExpirationDate() ? $user->getExpirationDate()->format('Y-m-d H:i:s') : null,
                    'last_login' => $user->getLastLogin() ? $user->getLastLogin()->format('Y-m-d H:i:s') : null,
                    'is_anonymous' => true,
                ];

                $this->session->set('_user', $userInfo);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    private function getOrCreateAnonymousUserId(string $userIp): ?int
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $trackLoginRepository = $this->entityManager->getRepository(TrackELogin::class);

        $maxAnonymousUsers = (int) $this->settingsManager->getSetting('admin.max_anonymous_users');
        if (0 === $maxAnonymousUsers) {
            $maxAnonymousUsers = self::MAX_ANONYMOUS_USERS;
        }
        $anonymousUsers = $userRepository->findBy(['status' => User::ANONYMOUS], ['registrationDate' => 'ASC']);

        // Check in TrackELogin if there is an anonymous user with the same IP
        foreach ($anonymousUsers as $user) {
            $loginRecord = $trackLoginRepository->findOneBy(['userIp' => $userIp, 'user' => $user]);
            if ($loginRecord) {
                return $user->getId();
            }
        }

        if (\count($anonymousUsers) >= $maxAnonymousUsers) {
            $oldestAnonymousUser = reset($anonymousUsers);
            if ($oldestAnonymousUser) {
                error_log('Updating oldest anonymous user: '.$oldestAnonymousUser->getId());

                $newUniqueId = uniqid('anon_');
                $newEmail = $newUniqueId.'@localhost.local';

                $oldestAnonymousUser->setUsername('anon_'.$newUniqueId)
                    ->setEmail($newEmail)
                    ->setLastLogin(new DateTime());

                $this->entityManager->persist($oldestAnonymousUser);
                $this->entityManager->flush();

                $this->updateOrCreateTrackELogin($userIp, $oldestAnonymousUser);

                return $oldestAnonymousUser->getId();
            }
        }

        // Create a new anonymous user
        $uniqueId = uniqid('anon_');
        $email = $uniqueId.'@localhost.local';

        if ('true' === $this->settingsManager->getSetting('profile.login_is_email')) {
            $uniqueId = $email;
        }

        $anonymousUser = (new User())
            ->setSkipResourceNode(true)
            ->setLastname('Joe')
            ->setFirstname('Anonymous')
            ->setUsername('anon_'.$uniqueId)
            ->setStatus(User::ANONYMOUS)
            ->setPlainPassword('anon')
            ->setEmail($email)
            ->setOfficialCode('anonymous')
            ->setCreatorId(1)
            ->addRole('ROLE_ANONYMOUS')
        ;

        $this->entityManager->persist($anonymousUser);
        $this->entityManager->flush();

        return $anonymousUser->getId();
    }

    private function updateOrCreateTrackELogin(string $userIp, User $user): void
    {
        $trackLoginRepository = $this->entityManager->getRepository(TrackELogin::class);
        $existingLogin = $trackLoginRepository->findOneBy(['userIp' => $userIp, 'user' => $user]);
        if (!$existingLogin) {
            $trackLogin = new TrackELogin();
            $trackLogin->setUserIp($userIp)
                ->setLoginDate(new DateTime())
                ->setUser($user);
            $this->entityManager->persist($trackLogin);
        } else {
            $existingLogin->setLoginDate(new DateTime());
            $this->entityManager->persist($existingLogin);
        }
        $this->entityManager->flush();
    }
}
