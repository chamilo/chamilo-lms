<?php
declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Symfony\Component\Security\Core\Security;


final class UsergroupDataProvider implements ProviderInterface
{
    private $security;
    private $usergroupRepository;
    private $illustrationRepository;

    public function __construct(Security $security, UsergroupRepository $usergroupRepository, IllustrationRepository $illustrationRepository)
    {
        $this->security = $security;
        $this->usergroupRepository = $usergroupRepository;
        $this->illustrationRepository = $illustrationRepository;
    }

    /**
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return iterable
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $operationName = $operation->getName();
        $userId = $context['request_attributes']['_api_filters']['userId'] ?? null;

        if (!$userId) {
            $user = $this->security->getUser();
            $userId = $user ? $user->getId() : null;
        }

        if (!$userId) {
            throw new \Exception("User ID is required");
        }

        switch ($operationName) {
            case 'get_my_usergroups':
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

        if (in_array($operationName, ['get_my_usergroups', 'get_newest_usergroups', 'get_popular_usergroups'])) {
            /* @var Usergroup $group */
            foreach ($groups as $group) {
                $memberCount = $this->usergroupRepository->countMembers($group->getId());
                $group->setMemberCount($memberCount);
                if ($this->illustrationRepository->hasIllustration($group)) {
                    $picture = $this->illustrationRepository->getIllustrationUrl($group);
                    $group->setPictureUrl($picture);
                }

            }
        }

        return $groups;
    }


    public function supports(Operation $operation, array $uriVariables = [], array $context = []): bool
    {
        return Usergroup::class === $operation->getClass();
    }
}
