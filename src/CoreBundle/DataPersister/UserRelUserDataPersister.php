<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Doctrine\ORM\EntityManager;

class UserRelUserDataPersister implements ContextAwareDataPersisterInterface
{
    private EntityManager $entityManager;
    private ContextAwareDataPersisterInterface $decorated;

    public function __construct(ContextAwareDataPersisterInterface $decorated, EntityManager $entityManager)
    {
        $this->decorated = $decorated;
        $this->entityManager = $entityManager;
    }

    public function supports($data, array $context = []): bool
    {
        return $this->decorated->supports($data, $context);
    }

    public function persist($data, array $context = [])
    {
        $result = $this->decorated->persist($data, $context);
        if ($data instanceof UserRelUser && (
                //($context['collection_operation_name'] ?? null) === 'post' ||
                //($context['graphql_operation_name'] ?? null) === 'create'
                ($context['item_operation_name'] ?? null) === 'put' // on update
        )
        ) {
            if (UserRelUser::USER_RELATION_TYPE_FRIEND === $data->getRelationType()) {
                //error_log((string)$data->getRelationType());
                $repo = $this->entityManager->getRepository(UserRelUser::class);
                // Check if the inverse connection is a friend request.
                $connection = $repo->findOneBy(
                    [
                        'user' => $data->getFriend(),
                        'friend' => $data->getUser(),
                        'relationType' => UserRelUser::USER_RELATION_TYPE_FRIEND,
                    ]
                );

                if (null === $connection) {
                    $connection = (new UserRelUser())
                        ->setUser($data->getFriend())
                        ->setFriend($data->getUser())
                        ->setRelationType(UserRelUser::USER_RELATION_TYPE_FRIEND)
                    ;
                    $this->entityManager->persist($connection);
                    $this->entityManager->flush();
                }
            }
        }

        // call persistence layer to save $data
        return $result;
    }

    public function remove($data, array $context = []): void
    {
        // call your persistence layer to delete $data
        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}
