<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class UserHelper
{
    public function __construct(
        private Security $security,
    ) {}

    public function getCurrent(): ?User
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user : null;
    }
}
