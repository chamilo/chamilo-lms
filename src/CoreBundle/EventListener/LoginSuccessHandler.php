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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginSuccessHandler
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $checker,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoginAttemptLogger $loginAttemptLogger,
        private readonly UserHelper $userHelper,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();
        $requestSession = $request->getSession();

        $user = $this->userHelper->getCurrent();

        // $userInfo = api_get_user_info($user->getId());
        // $userInfo['is_anonymous'] = false;

        // Backward compatibility.
        // $ip = $request->getClientIp();

        // Setting user info.
        // $requestSession->set('_user', $user);

        // Setting admin permissions for.
        if ($this->checker->isGranted('ROLE_ADMIN')) {
            $requestSession->set('is_platformAdmin', true);
        }

        // Setting teachers permissions.
        if ($this->checker->isGranted('ROLE_TEACHER')) {
            $requestSession->set('is_allowedCreateCourse', true);
        }

        // Setting last login datetime
        $requestSession->set('user_last_login_datetime', api_get_utc_datetime());

        $requestSession->set('_uid', $user->getId());
        // $requestSession->set('_user', $userInfo);
        // $requestSession->set('is_platformAdmin', \UserManager::is_admin($userId));
        // $requestSession->set('is_allowedCreateCourse', $userInfo['status'] === 1);
        // Redirecting to a course or a session.

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

            // Log of connection attempts
            $trackELoginRecordRepository->addTrackLogin($user->getUsername(), $userIp, true);
            $this->loginAttemptLogger->logAttempt(true, $user->getUsername(), $userIp);

            $requestSession->set('login_records_created', true);
        }
    }
}
