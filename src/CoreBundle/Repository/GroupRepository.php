<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\DataFixtures\AccessGroupFixtures;
use Chamilo\CoreBundle\Entity\Group;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    /**
     * @return User[]|Collection
     */
    public function getAdmins()
    {
        $criteria = [
            'name' => 'admins',
        ];

        /** @var Group $group */
        $group = $this->findOneBy($criteria);

        return $group->getUsers();
    }

    public function createDefaultGroups(AccessGroupFixtures $accessGroupFixtures = null): void
    {
        $groups = [
            [
                'code' => 'ADMIN',
                'title' => 'Administrators',
                'roles' => ['ROLE_ADMIN'],
            ],
            [
                'code' => 'STUDENT',
                'title' => 'Students',
                'roles' => ['ROLE_STUDENT'],
            ],
            [
                'code' => 'TEACHER',
                'title' => 'Teachers',
                'roles' => ['ROLE_TEACHER'],
            ],
            [
                'code' => 'RRHH',
                'title' => 'Human resources manager',
                'roles' => ['ROLE_RRHH'],
            ],
            [
                'code' => 'SESSION_MANAGER',
                'title' => 'Session',
                'roles' => ['ROLE_SESSION_MANAGER'],
            ],
            [
                'code' => 'QUESTION_MANAGER',
                'title' => 'Question manager',
                'roles' => ['ROLE_QUESTION_MANAGER'],
            ],
            [
                'code' => 'STUDENT_BOSS',
                'title' => 'Student boss',
                'roles' => ['ROLE_STUDENT_BOSS'],
            ],
            [
                'code' => 'INVITEE',
                'title' => 'Invitee',
                'roles' => ['ROLE_INVITEE'],
            ],
        ];

        $manager = $this->getEntityManager();

        foreach ($groups as $groupData) {
            $groupExists = $this->findOneBy(['code' => $groupData['code']]);
            if (null === $groupExists) {
                $group = new Group($groupData['title']);
                $group
                    ->setCode($groupData['code'])
                ;

                foreach ($groupData['roles'] as $role) {
                    $group->addRole($role);
                }
                $manager->persist($group);

                if (null !== $accessGroupFixtures) {
                    $accessGroupFixtures->addReference('GROUP_'.$groupData['code'], $group);
                }
            }
        }

        $manager->flush();
    }
}
