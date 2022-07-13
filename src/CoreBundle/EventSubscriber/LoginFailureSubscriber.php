<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class LoginFailureSubscriber implements EventSubscriberInterface
{
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

        error_log($username);
    }
}
