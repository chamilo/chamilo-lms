<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\LoginAttemptLoggerHelper;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class LoginFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TrackELoginRecordRepository $trackELoginRecordingRepository,
        private readonly RequestStack $requestStack,
        private readonly LoginAttemptLoggerHelper $loginAttemptLoggerHelper,
        private readonly SettingsManager $settingsManager,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => ['onFailureEvent', 10],
        ];
    }

    public function onFailureEvent(LoginFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $userIp = $request ? $request->getClientIp() : 'unknown';

        $passport = $event->getPassport();

        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);
        $username = $userBadge->getUserIdentifier();

        // Log of connection attempts
        $this->trackELoginRecordingRepository->addTrackLogin($username, $userIp, false);
        $this->loginAttemptLoggerHelper->logAttempt(false, $username, $userIp);

        $this->checkAndBlockAccount($username);
    }

    private function checkAndBlockAccount(string $username): void
    {
        $maxAttempts = (int) $this->settingsManager->getSetting('security.login_max_attempt_before_blocking_account', true);

        if ($maxAttempts <= 0) {
            return;
        }

        $recentFailures = $this->trackELoginRecordingRepository->countRecentFailedByUsername($username);

        if ($recentFailures < $maxAttempts) {
            return;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

        if ($user instanceof User && $user->getIsActive()) {
            $user->setActive(User::INACTIVE);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}
