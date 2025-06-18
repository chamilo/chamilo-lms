<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Usergroup;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @template-implements ProviderInterface<Usergroup>
 */
final class GroupMembersStateProvider implements ProviderInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function supports(Operation $operation, array $uriVariables = [], array $context = []): bool
    {
        return Usergroup::class === $operation->getClass() && 'get_group_members' === $operation->getName();
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $groupId = $uriVariables['id'] ?? null;

        if (null === $groupId) {
            return [];
        }

        $usergroupRepository = $this->entityManager->getRepository(Usergroup::class);

        return $usergroupRepository->getUsersByGroup((int) $groupId);
    }
}
