<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProcessorInterface<Usergroup, Usergroup>
 */
final class UsergroupPostStateProcessor implements ProcessorInterface
{
    private ProcessorInterface $processor;
    private EntityManagerInterface $entityManager;
    private Security $security;
    private RequestStack $requestStack;

    public function __construct(
        ProcessorInterface $processor,
        EntityManagerInterface $entityManager,
        Security $security,
        RequestStack $requestStack
    ) {
        $this->processor = $processor;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): Usergroup
    {
        /** @var Usergroup $usergroup */
        $usergroup = $this->processor->process($data, $operation, $uriVariables, $context);

        // A class has no moderator role: its members are the learners. Adding the creator (an administrator, or an
        // integration's service account) as a member would enroll them with the class and list them as a member.
        if ($usergroup instanceof Usergroup && Usergroup::NORMAL_CLASS !== $usergroup->getGroupType()) {
            $this->associateCurrentUser($usergroup);
            $this->entityManager->flush();
        }

        return $usergroup;
    }

    private function associateCurrentUser(Usergroup $usergroup): void
    {
        $currentUser = $this->security->getUser();
        if ($currentUser) {
            $usergroupRelUser = new UsergroupRelUser();
            $usergroupRelUser->setUsergroup($usergroup);
            $usergroupRelUser->setUser($currentUser);
            $usergroupRelUser->setRelationType(Usergroup::GROUP_USER_PERMISSION_ADMIN);

            $this->entityManager->persist($usergroupRelUser);
        }
    }
}
