<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AnonymousUserSubscriber implements EventSubscriberInterface
{
    private const MAX_ANONYMOUS_USERS = 5;

    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsManager $settingsManager,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (null !== $this->security->getUser()) {
            return;
        }

        $request = $event->getRequest();
        $userIp = $request->getClientIp() ?: '127.0.0.1';
        $accessUrl = $this->accessUrlHelper->getCurrent();

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
                    'active' => $user->isActive(),
                    'auth_sources' => $user->getAuthSourcesAuthentications($accessUrl),
                    'theme' => $user->getTheme(),
                    'language' => $user->getLocale(),
                    'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                    'expiration_date' => $user->getExpirationDate() ? $user->getExpirationDate()->format('Y-m-d H:i:s') : null,
                    'last_login' => $user->getLastLogin() ? $user->getLastLogin()->format('Y-m-d H:i:s') : null,
                    'is_anonymous' => true,
                ];

                $request->getSession()->set('_user', $userInfo);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    private function getOrCreateAnonymousUserId(string $userIp): ?int
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $trackLoginRepository = $this->entityManager->getRepository(TrackELogin::class);
        $anonymousAutoProvisioning = 'true' === $this->settingsManager->getSetting('security.anonymous_autoprovisioning');

        if (!$anonymousAutoProvisioning) {
            $anonymousUser = $userRepository->findOneBy(['status' => User::ANONYMOUS], ['createdAt' => 'ASC']);
            if ($anonymousUser) {
                return $anonymousUser->getId();
            }

            return $this->createAnonymousUser()->getId();
        }

        $maxAnonymousUsers = (int) $this->settingsManager->getSetting('admin.max_anonymous_users');
        if (0 === $maxAnonymousUsers) {
            $maxAnonymousUsers = self::MAX_ANONYMOUS_USERS;
        }
        $anonymousUsers = $userRepository->findBy(['status' => User::ANONYMOUS], ['createdAt' => 'ASC']);

        // Check in TrackELogin if there is an anonymous user with the same IP
        foreach ($anonymousUsers as $user) {
            $loginRecord = $trackLoginRepository->findOneBy(['userIp' => $userIp, 'user' => $user]);
            if ($loginRecord) {
                return $user->getId();
            }
        }

        // Delete excess anonymous users
        while (\count($anonymousUsers) >= $maxAnonymousUsers) {
            $oldestAnonymousUser = array_shift($anonymousUsers);
            if ($oldestAnonymousUser) {
                error_log('Deleting oldest anonymous user: '.$oldestAnonymousUser->getId());

                $this->entityManager->remove($oldestAnonymousUser);
                $this->entityManager->flush();
            }
        }

        return $this->createAnonymousUser()->getId();
    }

    private function createAnonymousUser(): User
    {
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

        return $anonymousUser;
    }
}
