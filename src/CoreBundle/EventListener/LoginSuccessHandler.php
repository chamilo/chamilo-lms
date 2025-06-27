<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\TrackELoginRecord;
use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Helpers\LoginAttemptLoggerHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use Chamilo\CoreBundle\Repository\TrackELoginRepository;
use Chamilo\CoreBundle\Repository\TrackEOnlineRepository;
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
        private readonly LoginAttemptLoggerHelper $loginAttemptLogger,
        private readonly UserHelper $userHelper
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
}
