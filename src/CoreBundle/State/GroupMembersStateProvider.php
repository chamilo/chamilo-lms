<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Security\Authorization\Voter\UsergroupVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @template-implements ProviderInterface<Usergroup>
 */
final class GroupMembersStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {}

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

        $usergroup = $usergroupRepository->find((int) $groupId);

        if (!$usergroup instanceof Usergroup) {
            throw new NotFoundHttpException('Usergroup not found.');
        }

        // Only members of the group (or platform admins) may read the member
        // roster, which exposes the PII (email, first/last name) of every member.
        if (!$this->security->isGranted(UsergroupVoter::VIEW, $usergroup)) {
            throw new AccessDeniedException('You are not allowed to view the members of this group.');
        }

        return $usergroupRepository->getUsersByGroup((int) $groupId);
    }
}
