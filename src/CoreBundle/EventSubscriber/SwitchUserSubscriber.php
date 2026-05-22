<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Authorization\LoginAsAuthorizationChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class SwitchUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LoginAsAuthorizationChecker $loginAsChecker,
    ) {}

    public function onSecuritySwitchUser(SwitchUserEvent $event): void
    {
        $request = $event->getRequest();

        /** @var User $targetUser */
        $targetUser = $event->getTargetUser();
        $newToken = $event->getToken();

        // Authorization guard: enforced on every switch-in attempt. Symfony's SwitchUserListener
        // only checks ROLE_ALLOWED_TO_SWITCH on the impersonator and ignores the target's
        // privileges, so we delegate the actual policy decision to LoginAsAuthorizationChecker,
        // which is the single source of truth shared with the legacy api_can_login_as() helper.
        if ($newToken instanceof SwitchUserToken) {
            $this->assertImpersonationAllowed($newToken->getOriginalToken(), $targetUser);
        }

        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        $session->set('_locale_user', $targetUser->getLocale());

        // Only show success flash when switching TO another user (not when exiting impersonation).
        if (!$newToken instanceof SwitchUserToken) {
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

    /**
     * @throws AccessDeniedException when the switch is not allowed
     */
    private function assertImpersonationAllowed(?TokenInterface $originalToken, User $targetUser): void
    {
        if (null === $originalToken) {
            throw new AccessDeniedException('Cannot impersonate without an original authenticated token.');
        }

        $impersonator = $originalToken->getUser();
        if (!$impersonator instanceof User) {
            throw new AccessDeniedException('Impersonator identity could not be resolved.');
        }

        if (!$this->loginAsChecker->canLoginAs($impersonator, $targetUser)) {
            throw new AccessDeniedException('Impersonation is not allowed by platform policy.');
        }
    }
}