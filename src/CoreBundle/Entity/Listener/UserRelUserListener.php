<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Exception;
use Symfony\Component\Security\Core\Security;

class UserRelUserListener
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function prePersist(UserRelUser $userRelUser, LifecycleEventArgs $args): void
    {
        // User cannot be connected to himself
        if ($userRelUser->getFriend()->getUsername() === $userRelUser->getUser()->getUsername()) {
            throw new Exception('Invalid relation UserRelUser');
        }
    }

    public function postRemove(UserRelUser $userRelUser, LifecycleEventArgs $args): void
    {
        // Deletes the other connection
        $em = $args->getEntityManager();
        $repo = $em->getRepository(UserRelUser::class);
        $connection = $repo->findOneBy(
            [
                'user' => $userRelUser->getFriend(),
                'friend' => $userRelUser->getUser(),
                'relationType' => $userRelUser->getRelationType(),
            ]
        );

        if (null !== $connection) {
            $em->remove($connection);
            $em->flush();
        }
    }
}
