<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

final readonly class UserAccessUrlListener
{
    public function __construct(
        private AccessUrlHelper $accessUrlHelper,
        private AccessUrlRepository $accessUrlRepo,
    ) {}

    public function __invoke(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $user = $passport->getUser();

        if (!$user instanceof User) {
            return;
        }

        $currentAccessUrl = $this->accessUrlHelper->getCurrent();

        try {
            $isAllowed = $this->accessUrlRepo->isUrlActiveForUser($currentAccessUrl, $user);
        } catch (NonUniqueResultException|NoResultException $e) {
            throw new CustomUserMessageAuthenticationException('Unable to verify user authentication');
        }

        if (!$isAllowed) {
            throw new CustomUserMessageAuthenticationException('Account inactive for this URL');
        }
    }
}
