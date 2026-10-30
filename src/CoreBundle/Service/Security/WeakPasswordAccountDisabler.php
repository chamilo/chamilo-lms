<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Security;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;

final readonly class WeakPasswordAccountDisabler
{
    public function __construct(
        private AccessUrlScopeHelper $accessUrlScope,
        private UserRepository $userRepository,
    ) {}

    public function canDisable(User $actor, User $target): bool
    {
        if (null === $actor->getId() || null === $target->getId()
            || (int) $actor->getId() === (int) $target->getId()
            || User::ACTIVE !== $target->getActive()
            || $target->hasRole('ROLE_ADMIN')
            || $target->hasRole('ROLE_GLOBAL_ADMIN')
        ) {
            return false;
        }

        return $this->accessUrlScope->canEditUser($actor, $target);
    }

    public function disable(User $actor, User $target): bool
    {
        if (!$this->canDisable($actor, $target)) {
            return false;
        }

        $target->setActive(User::INACTIVE);
        $this->userRepository->updateUser($target);

        return true;
    }
}
