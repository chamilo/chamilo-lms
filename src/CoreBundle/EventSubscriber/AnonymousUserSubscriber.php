<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use Chamilo\CoreBundle\Entity\User;

class AnonymousUserSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private EntityManagerInterface $entityManager;
    private SessionInterface $session;
    private SettingsManager $settingsManager;
    private const MAX_ANONYMOUS_USERS = 10;

    public function __construct(Security $security, EntityManagerInterface $entityManager, SessionInterface $session, SettingsManager $settingsManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->settingsManager = $settingsManager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->security->getUser() !== null) {
            return;
        }

        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $maxAnonymousUsers = (int) $this->settingsManager->getSetting('admin.max_anonymous_users');
        if (0 === $maxAnonymousUsers) {
            $maxAnonymousUsers = self::MAX_ANONYMOUS_USERS;
        }

        $userRepository = $this->entityManager->getRepository(User::class);

        if (!$this->session->has('anonymous_user_id')) {
            $anonymousUserCount = $userRepository->count(['status' => User::ANONYMOUS]);

            // Check if maximum number of anonymous users has been reached or exceeded
            if ($anonymousUserCount >= $maxAnonymousUsers) {
                // Remove all existing anonymous users
                $anonymousUsers = $userRepository->findBy(['status' => User::ANONYMOUS]);
                foreach ($anonymousUsers as $user) {
                    $this->entityManager->remove($user);
                }
                $this->entityManager->flush();
            }

            // Create a new anonymous user
            $uniqueId = uniqid();
            $anonymousUser = (new User())
                ->setSkipResourceNode(true)
                ->setLastname('Joe')
                ->setFirstname('Anonymous')
                ->setUsername('anon_' . $uniqueId)
                ->setStatus(User::ANONYMOUS)
                ->setPlainPassword('anon')
                ->setEmail('anon_' . $uniqueId . '@localhost.local')
                ->setOfficialCode('anonymous')
                ->setCreatorId(1);

            $this->entityManager->persist($anonymousUser);
            $this->entityManager->flush();

            $anonymousUserId = $anonymousUser->getId();
            $this->session->set('anonymous_user_id', $anonymousUserId);
        }

        if ($this->session->has('anonymous_user_id')) {
            $anonymousUserId = $this->session->get('anonymous_user_id');
            // Set or update the anonymous user information in the session
            $userInfo = [
                'user_id' => $anonymousUserId,
                'status' => User::ANONYMOUS,
                'is_anonymous' => true,
            ];
            $this->session->set('_user', $userInfo);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
