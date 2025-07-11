<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @template-implements ProviderInterface<Usergroup>
 */
final class UsergroupStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UsergroupRepository $usergroupRepository,
        private readonly IllustrationRepository $illustrationRepository
    ) {}

    /**
     * @throws Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $operationName = $operation->getName();
        if ('get_usergroup' === $operationName) {
            $groupId = $uriVariables['id'] ?? null;

            if (!$groupId) {
                throw new Exception("Group ID is required for 'get_usergroup' operation");
            }

            $group = $this->usergroupRepository->findGroupById($groupId);

            if (!$group) {
                throw new Exception('Group not found');
            }

            $this->setGroupDetails($group);

            return [$group];
        }

        if ('search_usergroups' === $operationName) {
            $searchTerm = $context['filters']['search'] ?? '';
            $groups = $this->usergroupRepository->searchGroups($searchTerm);
            foreach ($groups as $group) {
                $this->setGroupDetails($group);
            }

            return $groups;
        }

        switch ($operationName) {
            case 'get_my_usergroups':
                $userId = $context['request_attributes']['_api_filters']['userId'] ?? null;
                if (!$userId) {
                    /** @var User $user */
                    $user = $this->security->getUser();
                    $userId = $user?->getId();
                }
                if (!$userId) {
                    throw new Exception('User ID is required');
                }
                $groups = $this->usergroupRepository->getGroupsByUser($userId, 0);

                break;

            case 'get_newest_usergroups':
                $groups = $this->usergroupRepository->getNewestGroups();

                break;

            case 'get_popular_usergroups':
                $groups = $this->usergroupRepository->getPopularGroups();

                break;

            default:
                $groups = [];

                break;
        }

        if (\in_array($operationName, ['get_my_usergroups', 'get_newest_usergroups', 'get_popular_usergroups'])) {
            /** @var Usergroup $group */
            foreach ($groups as $group) {
                $this->setGroupDetails($group);
            }
        }

        return $groups;
    }

    public function supports(Operation $operation, array $uriVariables = [], array $context = []): bool
    {
        return Usergroup::class === $operation->getClass();
    }

    private function setGroupDetails(Usergroup $group): void
    {
        $memberCount = $this->usergroupRepository->countMembers($group->getId());
        $group->setMemberCount($memberCount);

        if ($this->illustrationRepository->hasIllustration($group)) {
            $picture = $this->illustrationRepository->getIllustrationUrl($group);
            $group->setPictureUrl($picture);
        }
    }
}
