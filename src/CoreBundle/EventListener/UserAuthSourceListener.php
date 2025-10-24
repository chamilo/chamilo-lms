<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Security\Badge\OAuth2Badge;
use Symfony\Component\Ldap\Security\LdapBadge;
use Symfony\Component\Ldap\Security\LdapUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

final readonly class UserAuthSourceListener
{
    public function __construct(
        private AccessUrlHelper $accessUrlHelper,
        private UserRepository $userRepo,
    ) {}

    public function __invoke(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $user = $passport->getUser();

        if ($user instanceof LdapUser) {
            $user = $this->userRepo->findOneBy(['username' => $user->getUserIdentifier()]);
        }

        if (!$user instanceof User) {
            return;
        }

        $currentAccessUrl = $this->accessUrlHelper->getCurrent();
        $authenticationTypes = $user->getAuthSourcesAuthentications($currentAccessUrl);

        if ($oAuth2Badge = $passport->getBadge(OAuth2Badge::class)) {
            if (!\in_array($oAuth2Badge->getAuthentication(), $authenticationTypes)) {
                throw new CustomUserMessageAuthenticationException('User authentication method not allowed');
            }

            return;
        }

        if ($passport->hasBadge(LdapBadge::class)) {
            if (!\in_array(UserAuthSource::LDAP, $authenticationTypes)) {
                throw new CustomUserMessageAuthenticationException('User authentication method not allowed');
            }

            return;
        }

        if (!\in_array(UserAuthSource::PLATFORM, $authenticationTypes)) {
            throw new CustomUserMessageAuthenticationException('User authentication method not allowed');
        }
    }
}
