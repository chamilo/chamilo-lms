<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SwitchUserSubscriber implements EventSubscriberInterface
{
    public function onSecuritySwitchUser(SwitchUserEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->hasSession() && ($session = $request->getSession())) {
            /** @var User $user */
            $user = $event->getTargetUser();

            $session->set('_locale_user', $user->getLocale());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'security.switch_user' => 'onSecuritySwitchUser',
        ];
    }
}
