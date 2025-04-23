<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProcessorInterface<UserRelUser, UserRelUser|void>
 */
final class UserRelUserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly ProcessorInterface $removeProcessor,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?UserRelUser
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        \assert($result instanceof UserRelUser);

        if ($operation instanceof Put) {
            if (UserRelUser::USER_RELATION_TYPE_FRIEND === $data->getRelationType()) {
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

        return $result;
    }
}
