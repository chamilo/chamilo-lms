<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class SwitchUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function onSecuritySwitchUser(SwitchUserEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        /** @var User $user */
        $user = $event->getTargetUser();
        $session->set('_locale_user', $user->getLocale());

        // Only show success flash when switching TO another user (not when exiting impersonation).
        if (!$event->getToken() instanceof SwitchUserToken) {
            return;
        }

        $homeUrl = $request->getSchemeAndHttpHost().'/';
        $homeLink = '<a href="'.$homeUrl.'">'.$homeUrl.'</a>';

        $flashBag = $session->getBag('flashes');
        if ($flashBag instanceof FlashBagInterface) {
            $flashBag->add('success', \sprintf($this->translator->trans('Login successful. Go to %s'), $homeLink));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'security.switch_user' => 'onSecuritySwitchUser',
        ];
    }
}
