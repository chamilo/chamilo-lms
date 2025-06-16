<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\TrackELoginRecord;
use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use Chamilo\CoreBundle\Repository\TrackELoginRepository;
use Chamilo\CoreBundle\Repository\TrackEOnlineRepository;
use Chamilo\CoreBundle\ServiceHelper\LoginAttemptLogger;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Routing\RouterInterface;
use Chamilo\CoreBundle\Settings\PlatformSettingsManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginSuccessHandler
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $checker,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoginAttemptLogger $loginAttemptLogger,
        private readonly UserHelper $userHelper,
        private readonly RouterInterface $router,
        private readonly PlatformSettingsManager $settingsManager,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();
        $requestSession = $request->getSession();

        $user = $this->userHelper->getCurrent();

        if ($this->checker->isGranted('ROLE_ADMIN')) {
            $requestSession->set('is_platformAdmin', true);
        }

        if ($this->checker->isGranted('ROLE_TEACHER')) {
            $requestSession->set('is_allowedCreateCourse', true);
        }

        $requestSession->set('user_last_login_datetime', api_get_utc_datetime());
        $requestSession->set('_uid', $user->getId());

        if (!$requestSession->get('login_records_created')) {
            $userIp = $request->getClientIp();

            /** @var TrackEOnlineRepository $trackEOnlineRepository */
            $trackEOnlineRepository = $this->entityManager->getRepository(TrackEOnline::class);

            /** @var TrackELoginRepository $trackELoginRepository */
            $trackELoginRepository = $this->entityManager->getRepository(TrackELogin::class);

            /** @var TrackELoginRecordRepository $trackELoginRecordRepository */
            $trackELoginRecordRepository = $this->entityManager->getRepository(TrackELoginRecord::class);

            $trackELoginRepository->createLoginRecord($user, new DateTime(), $userIp);
            $trackEOnlineRepository->createOnlineSession($user, $userIp);

            $user->setLastLogin(new DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $trackELoginRecordRepository->addTrackLogin($user->getUsername(), $userIp, true);
            $this->loginAttemptLogger->logAttempt(true, $user->getUsername(), $userIp);

            $requestSession->set('login_records_created', true);
        }
    }

    private function getRedirectAfterLoginUrl(): ?string
    {
        $json = $this->settingsManager->getSetting('registration.redirect_after_login', true);
        if (empty($json)) {
            return null;
        }

        $map = json_decode($json, true);
        if (!is_array($map)) {
            return null;
        }

        $profile = null;
        if ($this->checker->isGranted('ROLE_ADMIN')) {
            $profile = 'ADMIN';
        } elseif ($this->checker->isGranted('ROLE_SESSION_ADMIN')) {
            $profile = 'SESSIONADMIN';
        } elseif ($this->checker->isGranted('ROLE_TEACHER')) {
            $profile = 'COURSEMANAGER';
        } elseif ($this->checker->isGranted('ROLE_STUDENT_BOSS')) {
            $profile = 'STUDENT_BOSS';
        } elseif ($this->checker->isGranted('ROLE_DRH')) {
            $profile = 'DRH';
        } elseif ($this->checker->isGranted('ROLE_INVITEE')) {
            $profile = 'INVITEE';
        } elseif ($this->checker->isGranted('ROLE_STUDENT')) {
            $profile = 'STUDENT';
        }

        if ($profile !== null && isset($map[$profile]) && !empty($map[$profile])) {
            $target = trim($map[$profile]);

            return match ($target) {
                'user_portal.php', 'index.php' => $this->router->generate('index'),
                'main/auth/courses.php' => '/courses',
                default => rtrim(api_get_path(WEB_PATH), '/') . '/' . ltrim($target, '/'),
            };
        }

        return null;
    }
}
