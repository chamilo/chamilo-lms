<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
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
        $currentUser = $this->security->getUser();
        // User cannot be connected to himself
        if ($userRelUser->getFriend()->getUsername() === $currentUser->getUserIdentifier()) {
            throw new \Exception('Invalid relation UserRelUser');
        }
    }

    public function postRemove(UserRelUser $userRelUser, LifecycleEventArgs $args)
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
