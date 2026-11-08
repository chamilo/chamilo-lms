<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * The api firewall's json_login (/api/authentication_token) has no TOTP step,
 * so a password alone must not open an account protected by 2FA.
 */
final class ApiPasswordLoginMfaListener
{
    public function __invoke(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();

        if (!$passport->hasBadge(PasswordCredentials::class)) {
            return;
        }

        $user = $passport->getUser();

        if ($user instanceof User && $user->getMfaEnabled()) {
            throw new CustomUserMessageAuthenticationException('Two-factor authentication is required for this account.');
        }
    }
}
