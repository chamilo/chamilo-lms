<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\TrackELoginRecord;
use Chamilo\CoreBundle\Repository\TrackELoginRecordRepository;
use DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class LoginFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TrackELoginRecordRepository $trackELoginRecordingRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => ['onFailureEvent', 10],
        ];
    }

    public function onFailureEvent(LoginFailureEvent $event): void
    {
        $passport = $event->getPassport();
        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);
        $username = $userBadge->getUserIdentifier();

        // Log of connection attempts
        $trackELoginRecord = new TrackELoginRecord();
        $trackELoginRecord
            ->setUsername($username)
            ->setLoginDate(new DateTime())
            ->setUserIp(api_get_real_ip())
            ->setSuccess(false)
        ;
        $this->trackELoginRecordingRepository->create($trackELoginRecord);
    }
}
